<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\PhpGenerator\ClassType;
use olvlvl\ComposerAttributeCollector\Attributes;
use Override;
use PhpParser\NodeVisitor;
use PHPStan\Parser\RichParser;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;
use function sprintf;

#[ContainerExtension(name: 'validateServiceTags')]
final class ValidateServiceTagsExtension extends CompilerExtension
{

	/** @var array<string, array<class-string, string>> */
	private static array $interfaceTagMapping = [];

	/**
	 * Derived from the #[ExtensionInterface] attribute above each extension interface,
	 * both PHPStan's own ones and those found in the autowiredServiceDirectories.
	 *
	 * @return array<class-string, string>
	 */
	public static function getInterfaceTagMapping(ContainerBuilder $builder): array
	{
		require_once __DIR__ . '/../../vendor/attributes.php';

		$discoverer = AutowiredServiceDiscoverer::createFromContainerBuilder($builder);
		$cacheKey = $discoverer->getKey();
		if (array_key_exists($cacheKey, self::$interfaceTagMapping)) {
			return self::$interfaceTagMapping[$cacheKey];
		}

		$mapping = [
			// vendor interface - cannot carry the #[ExtensionInterface] attribute
			NodeVisitor::class => RichParser::VISITOR_SERVICE_TAG,
		];
		$classes = array_merge(
			Attributes::findTargetClasses(ExtensionInterface::class),
			$discoverer->findTargetClasses(ExtensionInterface::class),
		);
		foreach ($classes as $class) {
			// the attribute is not repeatable but the collector does not validate that
			if (array_key_exists($class->name, $mapping)) {
				throw new ShouldNotHappenException(sprintf('Interface %s claims multiple tags', $class->name));
			}
			$mapping[$class->name] = $class->attribute->tag;
		}

		return self::$interfaceTagMapping[$cacheKey] = $mapping;
	}

	/**
	 * Deliberately runs in afterCompile() rather than beforeCompile(): tags are added by other
	 * extensions' beforeCompile() - ConditionalTagsExtension, and any extension registered from a
	 * project config file, which the compiler always processes after PHPStan's own ones - and
	 * beforeCompile() hooks run in registration order, so validating there would only cover the
	 * extensions that happen to be registered earlier. Tags added after this point no longer reach
	 * the generated container, making this the first moment the tag list is complete.
	 *
	 * @throws MissingImplementedInterfaceInServiceWithTagException
	 */
	#[Override]
	public function afterCompile(ClassType $class): void
	{
		$builder = $this->getContainerBuilder();
		$mapping = self::getInterfaceTagMapping($builder);
		$mappingCount = count($mapping);
		$flippedMapping = array_flip($mapping);

		if (count($flippedMapping) !== $mappingCount) {
			throw new ShouldNotHappenException('A tag is mapped to multiple interfaces');
		}

		foreach ($builder->getDefinitions() as $definition) {
			/** @var class-string|null $className */
			$className = $definition->getType();
			if ($className === null) {
				continue;
			}
			$reflection = null;
			foreach (array_keys($definition->getTags()) as $tag) {
				if (!array_key_exists($tag, $flippedMapping)) {
					continue;
				}

				$reflection ??= new ReflectionClass($className);
				if ($reflection->implementsInterface($flippedMapping[$tag])) {
					continue;
				}

				throw new MissingImplementedInterfaceInServiceWithTagException($className, $tag, $flippedMapping[$tag]);
			}
		}
	}

}
