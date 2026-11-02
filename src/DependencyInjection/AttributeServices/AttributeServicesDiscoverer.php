<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\AttributeServices;

use olvlvl\ComposerAttributeCollector\TargetClass;
use olvlvl\ComposerAttributeCollector\TargetMethodParameter;
use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\ContainerExtension;
use PHPStan\DependencyInjection\ExtensionInterface;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\DependencyInjection\NonAutowiredService;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use PhpToken;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionAttribute;
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
use function is_string;
use function realpath;
use function sort;
use function sprintf;
use function str_contains;
use function str_ends_with;
use function str_starts_with;
use function stripos;
use function strtolower;
use function strtr;
use function trait_exists;
use function usort;
use const T_ATTRIBUTE;
use const T_CLASS;
use const T_INTERFACE;
use const T_NAME_QUALIFIED;
use const T_NAMESPACE;
use const T_STRING;
use const T_TRAIT;

/**
 * Finds classes carrying PHPStan's DI attributes in the directories listed
 * in the `attributeServicesDirectories` section.
 *
 * Runs only while the container compiles (a container-cache hit never gets here).
 * Class names are read from the tokens of the files that can use the attributes, the classes
 * are loaded by the registered autoloaders - Composer's of any type, or the one from
 * --autoload-file - and the attributes are read with native runtime reflection.
 * A file that looks like it uses one of the attributes but whose class cannot be
 * autoloaded from it is an explicit error.
 */
final class AttributeServicesDiscoverer
{

	public const PUBLIC_CLASS_ATTRIBUTES = [
		AutowiredService::class,
		NonAutowiredService::class,
		RegisteredRule::class,
		RegisteredCollector::class,
		GenerateFactory::class,
		Conditional::class,
		ExtensionInterface::class,
	];

	/**
	 * Classes allowed in `new` arguments of the attributes. Code that must run on PHP 8.0
	 * writes `new Foo(...)` as `[NEW_EXPRESSION_MARKER => [Foo::class, [arguments]]]`,
	 * like simple-downgrader does and composer-attribute-collector understands.
	 */
	private const NEW_EXPRESSION_CLASSES = [
		AutowiredService::class,
		RegisteredRule::class,
		RegisteredCollector::class,
	];

	private const NEW_EXPRESSION_MARKER = 'olvlvl\\ComposerAttributeCollector\\NewExpression';

	public const PUBLIC_PARAMETER_ATTRIBUTES = [
		AutowiredParameter::class,
	];

	private const ATTRIBUTE_SHORT_NAMES = [
		'AutowiredService',
		'NonAutowiredService',
		'RegisteredRule',
		'RegisteredCollector',
		'GenerateFactory',
		'Conditional',
		'AutowiredParameter',
		'AutowiredExtensions',
		'ContainerExtension',
		'ExtensionInterface',
		'ValidatesStubFiles',
	];

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
			foreach ($this->listPhpFiles($directory->directory) as $file) {
				$contents = FileReader::read($file);
				if (!self::isPrefilterPositive($contents)) {
					continue;
				}

				foreach (self::findDeclaredClasses($contents) as $className) {
					if (array_key_exists(strtolower($className), $seenClasses)) {
						continue;
					}
					$seenClasses[strtolower($className)] = true;

					$this->collectClass($className, $file, $contents, $targetClasses, $targetMethodParameters);
				}
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
	 * @param array<class-string, list<TargetClass<object>>> $targetClasses
	 * @param array<class-string, list<TargetMethodParameter<object>>> $targetMethodParameters
	 */
	private function collectClass(string $className, string $file, string $contents, array &$targetClasses, array &$targetMethodParameters): void
	{
		if (!$this->classCanBeLoaded($className)) {
			if (self::suggestsDiAttributes($contents)) {
				$this->errors[] = sprintf(
					'Class %s declared in %s (through a directory from the attributeServicesDirectories section) cannot be autoloaded.',
					$className,
					$file,
				);
			}

			return;
		}

		/** @var class-string $className */
		$reflection = new ReflectionClass($className);
		$loadedFile = $reflection->getFileName();
		if ($loadedFile === false || !self::isSameFile($loadedFile, $file)) {
			// another declaration of the class, like in test data, is none of our business
			if (self::suggestsDiAttributes($contents)) {
				$this->errors[] = sprintf(
					'Class %s declared in %s (through a directory from the attributeServicesDirectories section) is autoloaded from %s instead.',
					$className,
					$file,
					$loadedFile === false ? 'PHP itself' : $loadedFile,
				);
			}

			return;
		}

		foreach ($reflection->getAttributes() as $attribute) {
			$attributeClass = $this->resolveKnownAttribute($attribute->getName(), self::PUBLIC_CLASS_ATTRIBUTES);
			if ($attributeClass !== null) {
				try {
					$attributeInstance = $this->instantiateAttribute($attribute);
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

	/**
	 * @param ReflectionAttribute<object> $attribute
	 */
	private function instantiateAttribute(ReflectionAttribute $attribute): object
	{
		$arguments = $attribute->getArguments();
		$hasNewExpression = false;
		foreach ($arguments as $key => $argument) {
			$object = $this->instantiateNewExpression($argument);
			if ($object === null) {
				continue;
			}

			$arguments[$key] = $object;
			$hasNewExpression = true;
		}

		if (!$hasNewExpression) {
			return $attribute->newInstance();
		}

		// newInstanceArgs() instead of spreading because PHP 8.0 cannot spread string keys
		return (new ReflectionClass($attribute->getName()))->newInstanceArgs($arguments);
	}

	/**
	 * @throws ShouldNotHappenException
	 */
	private function instantiateNewExpression(mixed $value): ?object
	{
		if (!is_array($value) || count($value) !== 1 || !array_key_exists(self::NEW_EXPRESSION_MARKER, $value)) {
			return null;
		}

		$new = $value[self::NEW_EXPRESSION_MARKER];
		if (!is_array($new) || !isset($new[0], $new[1]) || !is_string($new[0]) || !is_array($new[1])) {
			throw new ShouldNotHappenException(sprintf('%s must be followed by [ClassName::class, [arguments]].', self::NEW_EXPRESSION_MARKER));
		}

		$class = $this->resolveKnownAttribute($new[0], self::NEW_EXPRESSION_CLASSES);
		if ($class === null) {
			throw new ShouldNotHappenException(sprintf('Class %s is not supported in %s.', $new[0], self::NEW_EXPRESSION_MARKER));
		}

		return (new ReflectionClass($class))->newInstanceArgs($new[1]);
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
			// autoload only once - an autoloader includes the file again on each attempt
			if (class_exists($className)) {
				return true;
			}

			return interface_exists($className, false)
				|| trait_exists($className, false)
				|| (function_exists('enum_exists') && enum_exists($className, false));
		} catch (Throwable) {
			return false;
		}
	}

	private static function isSameFile(string $a, string $b): bool
	{
		$realA = realpath($a);
		$realB = realpath($b);

		return $realA !== false && $realA === $realB;
	}

	/**
	 * Names of the classes, interfaces, traits and enums the file declares, none when the file
	 * has no attribute outside of comments and strings. Loading a class runs the code of its file,
	 * so classes of files that cannot carry the attributes, like rule test data, are not loaded.
	 *
	 * @return list<string>
	 */
	private static function findDeclaredClasses(string $contents): array
	{
		$tokens = PhpToken::tokenize($contents);
		$count = count($tokens);
		$namespace = '';
		$classes = [];
		$hasAttribute = false;
		for ($i = 0; $i < $count; $i++) {
			$token = $tokens[$i];
			if ($token->is(T_ATTRIBUTE)) {
				$hasAttribute = true;
				continue;
			}
			if ($token->is(T_NAMESPACE)) {
				$next = self::findNextSignificantToken($tokens, $i);
				$namespace = $next !== null && $next->is([T_STRING, T_NAME_QUALIFIED]) ? $next->text . '\\' : '';
				continue;
			}

			// T_ENUM does not exist on PHP 8.0
			if (!$token->is([T_CLASS, T_INTERFACE, T_TRAIT]) && $token->getTokenName() !== 'T_ENUM') {
				continue;
			}

			// skips Foo::class and anonymous classes
			$next = self::findNextSignificantToken($tokens, $i);
			if ($next === null || !$next->is(T_STRING)) {
				continue;
			}

			$classes[] = $namespace . $next->text;
		}

		return $hasAttribute ? $classes : [];
	}

	/**
	 * @param array<int, PhpToken> $tokens
	 */
	private static function findNextSignificantToken(array $tokens, int $i): ?PhpToken
	{
		$count = count($tokens);
		for ($j = $i + 1; $j < $count; $j++) {
			if (!$tokens[$j]->isIgnorable()) {
				return $tokens[$j];
			}
		}

		return null;
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
	 * Whether the file contents can change what discovery finds or reports. The container
	 * cache key of a directory hashes only these files, so editing any other file does not
	 * recompile the container.
	 */
	public static function mayAffectDiscovery(string $contents): bool
	{
		return self::isPrefilterPositive($contents) || self::suggestsDiAttributes($contents);
	}

	/**
	 * Cheap gate before the file is tokenized: any syntactically possible reference to one of
	 * PHPStan's attributes - a `use` import, a group use, a fully qualified name, an alias of
	 * either - contains "phpstan" and "dependencyinjection" case-insensitively, so a file
	 * without them cannot use them.
	 */
	private static function isPrefilterPositive(string $contents): bool
	{
		return str_contains($contents, '#[')
			&& stripos($contents, 'phpstan') !== false
			&& stripos($contents, 'dependencyinjection') !== false;
	}

	/**
	 * Stricter check used only to decide whether a file that yielded no autoloadable class
	 * deserves an error: does it mention any of the DI attributes by name?
	 */
	private static function suggestsDiAttributes(string $contents): bool
	{
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
