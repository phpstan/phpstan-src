<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use olvlvl\ComposerAttributeCollector\Attributes;
use Override;
use PHPStan\ShouldNotHappenException;
use ReflectionMethod;
use ReflectionNamedType;
use function array_key_exists;
use function sprintf;
use function str_replace;
use function strcasecmp;
use function strtolower;

/**
 * Registers an ExtensionsCollection service for each interface marked with
 * #[ExtensionInterface] and wires constructor parameters marked with
 * #[AutowiredExtensions] to them.
 *
 * Container::getExtensionsCollection() looks up the same services by name at runtime.
 */
final class AutowiredExtensionsExtension extends CompilerExtension
{

	public const SERVICE_NAME_PREFIX = 'phpstan.extensionsCollection.';

	public static function getCollectionServiceName(string $extensionInterfaceName): string
	{
		return self::SERVICE_NAME_PREFIX . str_replace('\\', '.', $extensionInterfaceName);
	}

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		foreach (ValidateServiceTagsExtension::getInterfaceTagMapping() as $interface => $tag) {
			$builder->addDefinition(self::getCollectionServiceName($interface))
				->setType(LazyExtensionsCollection::class)
				->setArgument('tagName', $tag)
				->setAutowired(false);
		}
	}

	#[Override]
	public function beforeCompile(): void
	{
		require_once __DIR__ . '/../../vendor/attributes.php';
		$builder = $this->getContainerBuilder();
		$mapping = ValidateServiceTagsExtension::getInterfaceTagMapping();

		$parametersByClass = [];
		foreach (Attributes::findTargetMethodParameters(AutowiredExtensions::class) as $parameter) {
			if (strcasecmp($parameter->method, '__construct') !== 0) {
				throw new ShouldNotHappenException(sprintf('Attribute #[AutowiredExtensions] is only supported on constructor parameters, found on %s::%s() $%s.', $parameter->class, $parameter->method, $parameter->name));
			}
			if (!array_key_exists($parameter->attribute->of, $mapping)) {
				throw new ShouldNotHappenException(sprintf('Interface %s referenced by #[AutowiredExtensions] on %s::__construct() $%s is not marked with the #[ExtensionInterface] attribute.', $parameter->attribute->of, $parameter->class, $parameter->name));
			}

			$nativeType = null;
			foreach ((new ReflectionMethod($parameter->class, '__construct'))->getParameters() as $constructorParameter) {
				if ($constructorParameter->getName() !== $parameter->name) {
					continue;
				}

				$nativeType = $constructorParameter->getType();
				break;
			}
			if (!$nativeType instanceof ReflectionNamedType || $nativeType->getName() !== ExtensionsCollection::class) {
				throw new ShouldNotHappenException(sprintf('Parameter $%s of %s::__construct() with #[AutowiredExtensions] must have the native type %s.', $parameter->name, $parameter->class, ExtensionsCollection::class));
			}

			$parametersByClass[strtolower($parameter->class)][] = $parameter;
		}

		foreach ($builder->getDefinitions() as $definition) {
			if ($definition instanceof FactoryDefinition) {
				$definition = $definition->getResultDefinition();
			}
			if (!$definition instanceof ServiceDefinition) {
				continue;
			}
			$className = $definition->getType();
			if ($className === null) {
				continue;
			}
			foreach ($parametersByClass[strtolower($className)] ?? [] as $parameter) {
				$definition->setArgument($parameter->name, new Reference(self::getCollectionServiceName($parameter->attribute->of)));
			}
		}
	}

}
