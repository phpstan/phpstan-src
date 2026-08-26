<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use olvlvl\ComposerAttributeCollector\TargetClass;
use olvlvl\ComposerAttributeCollector\TargetMethodParameter;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ContainerExtension;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\DependencyInjection\NonAutowiredService;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\File\FileReader;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionClass;
use SplFileInfo;
use Throwable;
use function array_key_exists;
use function class_exists;
use function count;
use function dirname;
use function enum_exists;
use function explode;
use function function_exists;
use function interface_exists;
use function is_array;
use function is_file;
use function is_string;
use function ksort;
use function preg_match;
use function sort;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function stripos;
use function strlen;
use function strtolower;
use function strtr;
use function substr;
use function trait_exists;
use function usort;

/**
 * Finds classes carrying PHPStan's DI attributes in the directories listed
 * in the `attributeServicesDirectories` section.
 *
 * Runs only while the container compiles (a container-cache hit never gets here).
 * Class names come from Composer's autoload data - the PSR-4 path contract and
 * vendor/composer/autoload_classmap.php - and the attributes are read with native
 * runtime reflection; no file is ever parsed. A file that looks like it uses one of
 * the attributes but whose class cannot be autoloaded is an explicit error.
 */
final class AttributeServicesDiscoverer
{

	public const PUBLIC_CLASS_ATTRIBUTES = [
		AutowiredService::class,
		NonAutowiredService::class,
		RegisteredRule::class,
		RegisteredCollector::class,
		GenerateFactory::class,
	];

	public const PUBLIC_PARAMETER_ATTRIBUTES = [
		AutowiredParameter::class,
	];

	private const ATTRIBUTE_SHORT_NAMES = [
		'AutowiredService',
		'NonAutowiredService',
		'RegisteredRule',
		'RegisteredCollector',
		'GenerateFactory',
		'AutowiredParameter',
		'AutowiredExtensions',
		'ContainerExtension',
		'ExtensionInterface',
		'ValidatesStubFiles',
	];

	/** @var array<string, array<string, list<string>>> autoload_classmap.php path => file => class names */
	private array $reverseClassmaps = [];

	/** @var list<string> */
	private array $errors = [];

	/**
	 * @throws InvalidAttributeServicesDirectoriesException
	 */
	public function discover(ResolvedAttributeServicesDirectories $resolvedDirectories): DiscoveredAttributeTargets
	{
		$this->errors = [];
		$targetClasses = [];
		$targetMethodParameters = [];
		$seenClasses = [];

		foreach ($resolvedDirectories->directories as $directory) {
			foreach ($this->findCandidateClasses($directory) as $className => $file) {
				if (array_key_exists(strtolower($className), $seenClasses)) {
					continue;
				}
				$seenClasses[strtolower($className)] = true;

				$this->collectClass($className, $file, $targetClasses, $targetMethodParameters);
			}
		}

		if (count($this->errors) > 0) {
			throw new InvalidAttributeServicesDirectoriesException($this->errors);
		}

		foreach ($targetClasses as $attributeClass => $targets) {
			usort($targets, static fn (TargetClass $a, TargetClass $b): int => $a->name <=> $b->name);
			$targetClasses[$attributeClass] = $targets;
		}
		foreach ($targetMethodParameters as $attributeClass => $targets) {
			usort($targets, static fn (TargetMethodParameter $a, TargetMethodParameter $b): int => [$a->class, $a->name] <=> [$b->class, $b->name]);
			$targetMethodParameters[$attributeClass] = $targets;
		}

		return new DiscoveredAttributeTargets($targetClasses, $targetMethodParameters);
	}

	/**
	 * Candidate classes of one directory, derived from Composer's autoload data. Files that
	 * cannot yield an autoloadable class are only an error when their contents suggest one
	 * of PHPStan's DI attributes - anything else in the directory is none of our business.
	 *
	 * @return array<string, string> class name => file
	 */
	private function findCandidateClasses(ResolvedAttributeServicesDirectory $directory): array
	{
		$candidates = [];
		foreach ($this->listPhpFiles($directory->directory) as $file) {
			$className = $this->derivePsr4ClassName($directory, $file);
			if ($className !== null) {
				$candidates[$className] = $file;
				continue;
			}

			$classmapClasses = $this->findClassmapClasses($directory, $file);
			if ($classmapClasses !== null) {
				if (count($classmapClasses) === 0) {
					if ($this->suggestsDiAttributes($file)) {
						$this->errors[] = sprintf(
							'File %s in a directory from the attributeServicesDirectories section is not present in Composer\'s class map. Run `composer dump-autoload` and try again.',
							$file,
						);
					}
					continue;
				}

				foreach ($classmapClasses as $classmapClass) {
					$candidates[$classmapClass] = $file;
				}
				continue;
			}

			if ($this->suggestsDiAttributes($file)) {
				$this->errors[] = sprintf(
					'File %s in a directory from the attributeServicesDirectories section is not covered by the Composer autoload rules of the directory, so its class cannot be autoloaded.',
					$file,
				);
			}
		}

		ksort($candidates);

		return $candidates;
	}

	/**
	 * @param array<class-string, list<TargetClass<object>>> $targetClasses
	 * @param array<class-string, list<TargetMethodParameter<object>>> $targetMethodParameters
	 */
	private function collectClass(string $className, string $file, array &$targetClasses, array &$targetMethodParameters): void
	{
		if (!$this->isPrefilterPositive($file)) {
			return;
		}

		if (!$this->classCanBeLoaded($className)) {
			if ($this->suggestsDiAttributes($file)) {
				$this->errors[] = sprintf(
					'Class %s expected in %s (through a directory from the attributeServicesDirectories section) cannot be autoloaded.',
					$className,
					$file,
				);
			}

			return;
		}

		/** @var class-string $className */
		$reflection = new ReflectionClass($className);

		foreach ($reflection->getAttributes() as $attribute) {
			$attributeClass = $this->resolveKnownAttribute($attribute->getName(), self::PUBLIC_CLASS_ATTRIBUTES);
			if ($attributeClass !== null) {
				try {
					$attributeInstance = $attribute->newInstance();
				} catch (Throwable $e) {
					$this->errors[] = sprintf('Cannot instantiate attribute #[%s] on class %s: %s', $this->getShortName($attribute->getName()), $className, $e->getMessage());
					continue;
				}

				$targetClasses[$attributeClass][] = new TargetClass($attributeInstance, $className);
				continue;
			}

			$this->checkDisallowedAttribute($attribute->getName(), $className);
		}

		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			return;
		}

		foreach ($constructor->getParameters() as $parameter) {
			foreach ($parameter->getAttributes() as $attribute) {
				$attributeClass = $this->resolveKnownAttribute($attribute->getName(), self::PUBLIC_PARAMETER_ATTRIBUTES);
				if ($attributeClass !== null) {
					try {
						$attributeInstance = $attribute->newInstance();
					} catch (Throwable $e) {
						$this->errors[] = sprintf('Cannot instantiate attribute #[%s] on a constructor parameter of class %s: %s', $this->getShortName($attribute->getName()), $className, $e->getMessage());
						continue;
					}

					$targetMethodParameters[$attributeClass][] = new TargetMethodParameter($attributeInstance, $className, $parameter->getName(), '__construct');
					continue;
				}

				$this->checkDisallowedAttribute($attribute->getName(), $className);
			}
		}
	}

	private function checkDisallowedAttribute(string $attributeName, string $className): void
	{
		if (!$this->isPhpStanAttribute($attributeName)) {
			return;
		}

		$lowerAttributeName = strtolower($attributeName);
		if ($lowerAttributeName === strtolower(ContainerExtension::class)) {
			$this->errors[] = sprintf(
				'Attribute #[ContainerExtension] on class %s is not supported in directories from the attributeServicesDirectories section - the list of compiler extensions is fixed before the section is processed. Register the class in the `extensions` section of the configuration file instead.',
				$className,
			);
			return;
		}

		if ($lowerAttributeName === strtolower(ExtensionInterface::class)) {
			$this->errors[] = sprintf(
				'Attribute #[ExtensionInterface] on %s is not supported in directories from the attributeServicesDirectories section - third-party extension interfaces are not supported.',
				$className,
			);
			return;
		}

		if ($lowerAttributeName === strtolower(AutowiredExtensions::class)) {
			$this->errors[] = sprintf(
				'Attribute #[AutowiredExtensions] on a constructor parameter of class %s is not supported in directories from the attributeServicesDirectories section.',
				$className,
			);
			return;
		}

		$this->errors[] = sprintf(
			'Attribute #[%s] on class %s is only supported on classes shipped with PHPStan itself, not on classes discovered through the attributeServicesDirectories section.',
			$this->getShortName($attributeName),
			$className,
		);
	}

	/**
	 * Canonical attribute class name when $attributeName is one of $allowedAttributes, null otherwise.
	 *
	 * @param list<class-string> $allowedAttributes
	 * @return class-string|null
	 */
	private function resolveKnownAttribute(string $attributeName, array $allowedAttributes): ?string
	{
		foreach ($allowedAttributes as $allowedAttribute) {
			if (strtolower($attributeName) === strtolower($allowedAttribute)) {
				return $allowedAttribute;
			}
		}

		return null;
	}

	private function isPhpStanAttribute(string $attributeName): bool
	{
		if (!class_exists($attributeName)) {
			return false;
		}

		$file = (new ReflectionClass($attributeName))->getFileName();
		if ($file === false) {
			return false;
		}

		$phpstanRoot = strtr(dirname(__DIR__, 3), '\\', '/');

		return str_starts_with(strtr($file, '\\', '/'), $phpstanRoot . '/');
	}

	private function classCanBeLoaded(string $className): bool
	{
		try {
			if (class_exists($className) || interface_exists($className) || trait_exists($className)) {
				return true;
			}

			return function_exists('enum_exists') && enum_exists($className);
		} catch (Throwable) {
			return false;
		}
	}

	/**
	 * @return list<string> normalized with forward slashes, sorted
	 */
	private function listPhpFiles(string $directory): array
	{
		$files = [];
		$iterator = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator($directory, RecursiveDirectoryIterator::SKIP_DOTS),
		);
		foreach ($iterator as $fileInfo) {
			if (!$fileInfo instanceof SplFileInfo || !$fileInfo->isFile()) {
				continue;
			}
			if (!str_ends_with($fileInfo->getFilename(), '.php')) {
				continue;
			}

			$files[] = strtr($fileInfo->getPathname(), '\\', '/');
		}

		sort($files);

		return $files;
	}

	/**
	 * FQCN of the file per the PSR-4 path contract, or null when no PSR-4 rule of the
	 * directory covers the file (or a path segment cannot be a PHP name).
	 */
	private function derivePsr4ClassName(ResolvedAttributeServicesDirectory $directory, string $file): ?string
	{
		$bestBaseDirectory = null;
		$bestPrefix = null;
		foreach ($directory->psr4 as $namespacePrefix => $baseDirectories) {
			foreach ($baseDirectories as $baseDirectory) {
				if (!str_starts_with($file, $baseDirectory . '/')) {
					continue;
				}
				if ($bestBaseDirectory !== null && strlen($baseDirectory) <= strlen($bestBaseDirectory)) {
					continue;
				}

				$bestBaseDirectory = $baseDirectory;
				$bestPrefix = $namespacePrefix;
			}
		}

		if ($bestBaseDirectory === null || $bestPrefix === null) {
			return null;
		}

		$relativePath = substr($file, strlen($bestBaseDirectory) + 1, -4);
		$segments = explode('/', $relativePath);
		foreach ($segments as $segment) {
			if (preg_match('/^[a-zA-Z_\x80-\xff][a-zA-Z0-9_\x80-\xff]*$/', $segment) !== 1) {
				return null;
			}
		}

		return $bestPrefix . strtr($relativePath, '/', '\\');
	}

	/**
	 * Class names Composer's class map records for the file, an empty list when the file
	 * sits under a classmap rule but the map does not know it (a stale dump), or null
	 * when no classmap rule of the directory covers the file.
	 *
	 * @return list<string>|null
	 */
	private function findClassmapClasses(ResolvedAttributeServicesDirectory $directory, string $file): ?array
	{
		$covered = false;
		foreach ($directory->classmapPaths as $classmapPath) {
			if ($file === $classmapPath || str_starts_with($file, $classmapPath . '/')) {
				$covered = true;
				break;
			}
		}

		if (!$covered) {
			return null;
		}

		$reverseClassmap = $this->getReverseClassmap($directory->autoloadClassmapPath);

		return $reverseClassmap[$file] ?? [];
	}

	/**
	 * @return array<string, list<string>> file => class names
	 */
	private function getReverseClassmap(string $autoloadClassmapPath): array
	{
		if (array_key_exists($autoloadClassmapPath, $this->reverseClassmaps)) {
			return $this->reverseClassmaps[$autoloadClassmapPath];
		}

		$reverse = [];
		if (is_file($autoloadClassmapPath)) {
			$classmap = require $autoloadClassmapPath;
			if (is_array($classmap)) {
				foreach ($classmap as $className => $file) {
					if (!is_string($className) || !is_string($file)) {
						continue;
					}

					$reverse[strtr($file, '\\', '/')][] = $className;
				}
			}
		}

		return $this->reverseClassmaps[$autoloadClassmapPath] = $reverse;
	}

	/**
	 * Cheap gate before a class is autoloaded: any syntactically possible reference to one of
	 * PHPStan's attributes - a `use` import, a fully qualified name, an alias of either -
	 * contains the string "phpstan" case-insensitively, so a file without it cannot use them.
	 */
	private function isPrefilterPositive(string $file): bool
	{
		$contents = FileReader::read($file);

		return str_contains($contents, '#[') && stripos($contents, 'phpstan') !== false;
	}

	/**
	 * Stricter check used only to decide whether a file that yielded no autoloadable class
	 * deserves an error: does it mention any of the DI attributes by name?
	 */
	private function suggestsDiAttributes(string $file): bool
	{
		$contents = FileReader::read($file);
		if (!str_contains($contents, '#[')) {
			return false;
		}

		foreach (self::ATTRIBUTE_SHORT_NAMES as $shortName) {
			if (stripos($contents, $shortName) !== false) {
				return true;
			}
		}

		return false;
	}

	private function getShortName(string $className): string
	{
		$parts = explode('\\', $className);

		return $parts[count($parts) - 1];
	}

}
