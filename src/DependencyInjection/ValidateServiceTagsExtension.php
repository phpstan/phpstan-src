<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use olvlvl\ComposerAttributeCollector\Attributes;
use Override;
use PhpParser\NodeVisitor;
use PHPStan\Parser\RichParser;
use PHPStan\ShouldNotHappenException;
use ReflectionClass;
use function array_flip;
use function array_key_exists;
use function array_keys;
use function count;
use function sprintf;

final class ValidateServiceTagsExtension extends CompilerExtension
{

	/** @var array<class-string, string>|null */
	private static ?array $interfaceTagMapping = null;

	/**
	 * Derived from the #[ExtensionInterface] attribute above each extension interface.
	 *
	 * @return array<class-string, string>
	 */
	public static function getInterfaceTagMapping(): array
	{
		if (self::$interfaceTagMapping !== null) {
			return self::$interfaceTagMapping;
		}

		require_once __DIR__ . '/../../vendor/attributes.php';

		$mapping = [
			// vendor interface - cannot carry the #[ExtensionInterface] attribute
			NodeVisitor::class => RichParser::VISITOR_SERVICE_TAG,
		];
		foreach (Attributes::findTargetClasses(ExtensionInterface::class) as $class) {
			// the attribute is not repeatable but the collector does not validate that
			if (array_key_exists($class->name, $mapping)) {
				throw new ShouldNotHappenException(sprintf('Interface %s claims multiple tags', $class->name));
			}
			$mapping[$class->name] = $class->attribute->tag;
		}

		return self::$interfaceTagMapping = $mapping;
	}

	/**
	 * @throws MissingImplementedInterfaceInServiceWithTagException
	 */
	#[Override]
	public function beforeCompile(): void
	{
		$builder = $this->getContainerBuilder();
		$mapping = self::getInterfaceTagMapping();
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
			$reflection = new ReflectionClass($className);
			foreach (array_keys($definition->getTags()) as $tag) {
				if (!array_key_exists($tag, $flippedMapping)) {
					continue;
				}

				if ($reflection->implementsInterface($flippedMapping[$tag])) {
					continue;
				}

				throw new MissingImplementedInterfaceInServiceWithTagException($className, $tag, $flippedMapping[$tag]);
			}
		}
	}

}
