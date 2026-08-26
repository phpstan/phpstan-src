<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\ContainerBuilder;
use olvlvl\ComposerAttributeCollector\TargetClass;
use olvlvl\ComposerAttributeCollector\TargetMethodParameter;
use PhpParser\Error;
use PhpParser\Node\Stmt\ClassLike;
use PhpParser\NodeFinder;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitor\NameResolver;
use PhpParser\ParserFactory;
use PHPStan\File\FileReader;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use Symfony\Component\Finder\Finder;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function array_unique;
use function array_values;
use function class_exists;
use function count;
use function implode;
use function in_array;
use function interface_exists;
use function is_dir;
use function sort;
use function sprintf;
use function str_contains;
use function strrpos;
use function substr;

/**
 * Finds classes marked with PHPStan's dependency injection attributes in the directories
 * listed in the `autowiredServiceDirectories` parameter.
 *
 * PHPStan's own services are collected at composer install time by
 * ondrejmirtes/composer-attribute-collector into vendor/attributes.php. That file only ever
 * covers phpstan-src's own `src` directory and is bundled into the PHAR, where the collector
 * lives under a build-specific namespace prefix---extensions distributed as separate packages
 * cannot contribute to it. They point this class at their source directories instead, and the
 * results are merged with vendor/attributes.php by the compiler extensions consuming them.
 *
 * Each configuration file contributes its own directories to the parameter, so extensions
 * never see each other's classes---because the parameter is a list, one extension adding
 * to it cannot overwrite another's.
 *
 * @internal
 */
final class AutowiredServiceDiscoverer
{

	private const CLASS_ATTRIBUTES = [
		AutowiredService::class,
		NonAutowiredService::class,
		RegisteredRule::class,
		RegisteredCollector::class,
		GenerateFactory::class,
		ExtensionInterface::class,
		ValidatesStubFiles::class,
	];

	private const CONSTRUCTOR_PARAMETER_ATTRIBUTES = [
		AutowiredParameter::class,
		AutowiredExtensions::class,
	];

	/**
	 * Compiler extensions have to be registered before the compiler snapshots its extension list,
	 * which is earlier than anything here runs. The `extensions:` section of a configuration file
	 * does the same job for classes outside phpstan-src.
	 *
	 */
	private const UNSUPPORTED_ATTRIBUTES = [
		ContainerExtension::class,
	];

	/** @var array<string, self> */
	private static array $instances = [];

	/** @var array<string, list<string>> */
	private static array $files = [];

	/** @var list<string>|null */
	private static ?array $attributeNamespaces = null;

	private bool $collected = false;

	/** @var array<class-string, list<TargetClass<object>>> */
	private array $targetClasses = [];

	/** @var array<class-string, list<TargetMethodParameter<object>>> */
	private array $targetMethodParameters = [];

	/**
	 * @param list<string> $directories
	 */
	private function __construct(private string $key, private array $directories)
	{
	}

	/**
	 * @param list<string> $directories
	 */
	public static function create(array $directories): self
	{
		$directories = self::normalizeDirectories($directories);
		$key = implode("\n", $directories);

		return self::$instances[$key] ??= new self($key, $directories);
	}

	/**
	 * Identifies the scanned set of directories. Callers memoizing anything derived from a
	 * discoverer key their caches on it, because two containers compiled in the same process
	 * can be configured with different directories.
	 */
	public function getKey(): string
	{
		return $this->key;
	}

	public static function createFromContainerBuilder(ContainerBuilder $builder): self
	{
		/** @var list<string> $directories */
		$directories = $builder->parameters['autowiredServiceDirectories'];

		return self::create($directories);
	}

	/**
	 * Lists the files `create()` reads, without parsing any of them. ContainerFactory hashes them
	 * into the container cache key, so that editing a discovered class rebuilds the container.
	 *
	 * @param list<string> $directories
	 * @return list<string>
	 */
	public static function findFiles(array $directories): array
	{
		$directories = self::normalizeDirectories($directories);
		$key = implode("\n", $directories);
		if (array_key_exists($key, self::$files)) {
			return self::$files[$key];
		}

		$files = [];
		foreach ($directories as $directory) {
			if (!is_dir($directory)) {
				throw new ShouldNotHappenException(sprintf('Directory %s from the autowiredServiceDirectories parameter does not exist.', $directory));
			}

			$finder = new Finder();
			$finder->followLinks()->files()->name('*.php')->in($directory);
			foreach ($finder as $fileInfo) {
				$files[] = $fileInfo->getPathname();
			}
		}

		$files = array_values(array_unique($files));
		sort($files);

		return self::$files[$key] = $files;
	}

	/**
	 * @template T of object
	 * @param class-string<T> $attribute
	 * @return list<TargetClass<T>>
	 */
	public function findTargetClasses(string $attribute): array
	{
		$this->collect();

		/** @var list<TargetClass<T>> */
		return $this->targetClasses[$attribute] ?? [];
	}

	/**
	 * @template T of object
	 * @param class-string<T> $attribute
	 * @return list<TargetMethodParameter<T>>
	 */
	public function findTargetMethodParameters(string $attribute): array
	{
		$this->collect();

		/** @var list<TargetMethodParameter<T>> */
		return $this->targetMethodParameters[$attribute] ?? [];
	}

	/**
	 * Reads and parses the discovered files on the first lookup. Compiler extensions ask for
	 * attributes they do not always use - a container compiled without any of them never
	 * touches the filesystem beyond the file list ContainerFactory needs anyway.
	 */
	private function collect(): void
	{
		if ($this->collected) {
			return;
		}

		$this->collected = true;

		$parser = (new ParserFactory())->createForNewestSupportedVersion();
		$nodeFinder = new NodeFinder();
		$discoveredClasses = [];

		foreach (self::findFiles($this->directories) as $file) {
			$contents = FileReader::read($file);
			if (!self::mayContainAttributes($contents)) {
				continue;
			}

			try {
				$stmts = $parser->parse($contents);
			} catch (Error $e) {
				throw new ShouldNotHappenException(sprintf('Cannot parse %s: %s', $file, $e->getMessage()));
			}
			if ($stmts === null) {
				continue;
			}

			$traverser = new NodeTraverser(new NameResolver());
			$stmts = $traverser->traverse($stmts);

			foreach ($nodeFinder->findInstanceOf($stmts, ClassLike::class) as $class) {
				if ($class->namespacedName === null) {
					// anonymous class
					continue;
				}

				$attributeNames = self::findAttributeNames($class);
				if (count($attributeNames) === 0) {
					continue;
				}

				/** @var class-string $className */
				$className = $class->namespacedName->toString();
				foreach ($attributeNames as $attributeName) {
					if (!in_array($attributeName, self::UNSUPPORTED_ATTRIBUTES, true)) {
						continue;
					}

					throw new ShouldNotHappenException(sprintf(
						'Attribute #[%s] on class %s is only supported for classes shipped with PHPStan itself, not for classes discovered through the autowiredServiceDirectories parameter.',
						self::shortName($attributeName),
						$className,
					));
				}

				if (array_key_exists($className, $discoveredClasses)) {
					// the same file can be reachable through two overlapping directories
					continue;
				}
				$discoveredClasses[$className] = true;

				$this->collectClass($className, $file);
			}
		}
	}

	/**
	 * @param class-string $className
	 */
	private function collectClass(string $className, string $file): void
	{
		if (!class_exists($className) && !interface_exists($className)) {
			throw new ShouldNotHappenException(sprintf(
				'Class %s declared in %s has a PHPStan dependency injection attribute but cannot be autoloaded.',
				$className,
				$file,
			));
		}

		$reflection = new ReflectionClass($className);
		foreach (self::CLASS_ATTRIBUTES as $attributeClass) {
			foreach ($reflection->getAttributes($attributeClass) as $attribute) {
				$this->addTargetClass($attributeClass, $className, $attribute->newInstance());
			}
		}

		$constructor = $reflection->getConstructor();
		if ($constructor === null) {
			return;
		}

		foreach ($constructor->getParameters() as $parameter) {
			foreach (self::CONSTRUCTOR_PARAMETER_ATTRIBUTES as $attributeClass) {
				foreach ($parameter->getAttributes($attributeClass) as $attribute) {
					$this->addTargetMethodParameter($attributeClass, $className, $parameter->getName(), $attribute->newInstance());
				}
			}
		}
	}

	/**
	 * @param class-string $attributeClass
	 * @param class-string $className
	 */
	private function addTargetClass(string $attributeClass, string $className, object $attribute): void
	{
		$this->targetClasses[$attributeClass][] = new TargetClass($attribute, $className);
	}

	/**
	 * @param class-string $attributeClass
	 * @param class-string $className
	 * @param non-empty-string $parameterName
	 */
	private function addTargetMethodParameter(string $attributeClass, string $className, string $parameterName, object $attribute): void
	{
		$this->targetMethodParameters[$attributeClass][] = new TargetMethodParameter($attribute, $className, $parameterName, '__construct');
	}

	/**
	 * Names of PHPStan's dependency injection attributes on the class itself and on its
	 * constructor parameters. Everything else in the scanned directories is left alone.
	 *
	 * @return list<string>
	 */
	private static function findAttributeNames(ClassLike $class): array
	{
		$known = array_merge(self::CLASS_ATTRIBUTES, self::CONSTRUCTOR_PARAMETER_ATTRIBUTES, self::UNSUPPORTED_ATTRIBUTES);
		$attrGroups = $class->attrGroups;
		$constructor = $class->getMethod('__construct');
		if ($constructor !== null) {
			foreach ($constructor->params as $param) {
				$attrGroups = array_merge($attrGroups, $param->attrGroups);
			}
		}

		$names = [];
		foreach ($attrGroups as $attrGroup) {
			foreach ($attrGroup->attrs as $attr) {
				$name = $attr->name->toString();
				if (!in_array($name, $known, true)) {
					continue;
				}

				$names[] = $name;
			}
		}

		return array_values(array_unique($names));
	}

	/**
	 * Cheap pre-filter so that files without any of PHPStan's attributes are never parsed.
	 *
	 * Looking for the namespace the attributes live in rather than for each of their names keeps
	 * this down to a single needle per file, and covers every way of referring to them: a plain,
	 * grouped or aliased `use` line, a fully qualified name written in place, and - because the
	 * `namespace` declaration matches too - an unqualified name in a file that already sits in
	 * that namespace.
	 */
	private static function mayContainAttributes(string $contents): bool
	{
		if (!str_contains($contents, '#[')) {
			return false;
		}

		foreach (self::getAttributeNamespaces() as $namespace) {
			if (str_contains($contents, $namespace)) {
				return true;
			}
		}

		return false;
	}

	/**
	 * @return list<string>
	 */
	private static function getAttributeNamespaces(): array
	{
		if (self::$attributeNamespaces !== null) {
			return self::$attributeNamespaces;
		}

		$namespaces = [];
		foreach (array_merge(self::CLASS_ATTRIBUTES, self::CONSTRUCTOR_PARAMETER_ATTRIBUTES, self::UNSUPPORTED_ATTRIBUTES) as $attributeClass) {
			$position = strrpos($attributeClass, '\\');
			if ($position === false) {
				continue;
			}

			$namespaces[substr($attributeClass, 0, $position)] = true;
		}

		return self::$attributeNamespaces = array_keys($namespaces);
	}

	private static function shortName(string $className): string
	{
		$position = strrpos($className, '\\');
		if ($position === false) {
			return $className;
		}

		return substr($className, $position + 1);
	}

	/**
	 * @param list<string> $directories
	 * @return list<string>
	 */
	private static function normalizeDirectories(array $directories): array
	{
		$directories = array_values(array_unique($directories));
		sort($directories);

		return $directories;
	}

}
