<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\DI\ContainerBuilder;
use Nette\DI\Definitions\FactoryDefinition;
use Nette\DI\Definitions\Reference;
use Nette\DI\Definitions\ServiceDefinition;
use Nette\DI\Definitions\Statement;
use Nette\DI\Helpers;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Nette\Utils\Strings;
use olvlvl\ComposerAttributeCollector\Attributes;
use olvlvl\ComposerAttributeCollector\TargetMethodParameter;
use Override;
use PHPStan\Collectors\RegistryFactory;
use PHPStan\Rules\LazyRegistry;
use ReflectionClass;
use stdClass;
use function array_key_exists;
use function count;
use function explode;
use function strcasecmp;
use function strtolower;
use function substr;

final class AutowiredAttributeServicesExtension extends CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema
	{
		return Expect::structure([
			'level' => Expect::int()->nullable()->required(),
		]);
	}

	#[Override]
	public function loadConfiguration(): void
	{
		require_once __DIR__ . '/../../vendor/attributes.php';
		$builder = $this->getContainerBuilder();

		// Bakes the #[ExtensionInterface] mapping into the compiled container
		// so that Container::getExtensions() does not need vendor/attributes.php at runtime.
		$builder->addDefinition($this->prefix('extensionInterfaceTags'))
			->setType(ExtensionInterfaceTags::class)
			->setArguments([ValidateServiceTagsExtension::getInterfaceTagMapping()]);

		$autowiredParameters = Attributes::findTargetMethodParameters(AutowiredParameter::class);
		$constructorParameters = [];
		foreach ($autowiredParameters as $parameter) {
			if (strcasecmp($parameter->method, '__construct') !== 0) {
				continue;
			}
			$lowerClass = strtolower($parameter->class);
			$constructorParameters[$lowerClass] ??= [];
			$constructorParameters[$lowerClass][] = $parameter;
		}

		foreach (Attributes::findTargetClasses(AutowiredService::class) as $class) {
			$reflection = new ReflectionClass($class->name);
			$attribute = $class->attribute;

			$definition = $builder->addDefinition($attribute->name)
				->setType($class->name)
				->setAutowired($attribute->as);

			if ($attribute->factory !== null) {
				[$ref, $method] = explode('::', $attribute->factory);
				$definition->setFactory(new Statement([new Reference(substr($ref, 1)), $method]));
			}

			self::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);

			if (!$attribute->autoTag) {
				continue;
			}

			foreach (ValidateServiceTagsExtension::getInterfaceTagMapping() as $interface => $tag) {
				if (!$reflection->implementsInterface($interface)) {
					continue;
				}

				$definition->addTag($tag);
			}
		}

		foreach (Attributes::findTargetClasses(NonAutowiredService::class) as $class) {
			$attribute = $class->attribute;

			$definition = $builder->addDefinition($attribute->name)
				->setType($class->name)
				->setAutowired(false);

			if ($attribute->factory !== null) {
				[$ref, $method] = explode('::', $attribute->factory);
				$definition->setFactory(new Statement([new Reference(substr($ref, 1)), $method]));
			}

			self::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
		}

		foreach (Attributes::findTargetClasses(GenerateFactory::class) as $class) {
			$attribute = $class->attribute;
			$definition = $builder->addFactoryDefinition(null)
				->setImplement($attribute->interface);

			if ($attribute->resultType !== null) {
				$definition->getResultDefinition()->setType($attribute->resultType);
			}

			$resultDefinition = $definition->getResultDefinition();
			self::processConstructorParameters($builder, $class->name, $resultDefinition, $constructorParameters);
		}

		/** @var stdClass&object{level: int|null} $config */
		$config = $this->getConfig();
		if ($config->level === null) {
			return;
		}

		foreach (Attributes::findTargetClasses(RegisteredRule::class) as $class) {
			$attribute = $class->attribute;
			if ($attribute->level > $config->level) {
				continue;
			}

			$definition = $builder->addDefinition(null)
				->setFactory($class->name)
				->setAutowired($class->name)
				->addTag(LazyRegistry::RULE_TAG);

			self::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
		}

		foreach (Attributes::findTargetClasses(RegisteredCollector::class) as $class) {
			$attribute = $class->attribute;
			if ($attribute->level > $config->level) {
				continue;
			}

			$definition = $builder->addDefinition(null)
				->setFactory($class->name)
				->setAutowired($class->name)
				->addTag(RegistryFactory::COLLECTOR_TAG);

			self::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
		}
	}

	/**
	 * Wires #[AutowiredExtensions] constructor parameters.
	 *
	 * It has to happen in beforeCompile() and not in loadConfiguration(): services from the NEON
	 * files are registered by Nette's own ServicesExtension after every other extension's
	 * loadConfiguration(), so they are not in the builder yet at that point.
	 *
	 * @throws NotAnExtensionInterfaceException
	 */
	#[Override]
	public function beforeCompile(): void
	{
		require_once __DIR__ . '/../../vendor/attributes.php';

		/** @var array<lowercase-string, non-empty-list<TargetMethodParameter<AutowiredExtensions>>> $constructorParameters */
		$constructorParameters = [];
		foreach (Attributes::findTargetMethodParameters(AutowiredExtensions::class) as $parameter) {
			if (strcasecmp($parameter->method, '__construct') !== 0) {
				continue;
			}
			$constructorParameters[strtolower($parameter->class)][] = $parameter;
		}

		if (count($constructorParameters) === 0) {
			return;
		}

		$mapping = ValidateServiceTagsExtension::getInterfaceTagMapping();
		$builder = $this->getContainerBuilder();

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

			foreach ($constructorParameters[strtolower($className)] ?? [] as $parameter) {
				$interface = $parameter->attribute->interface;
				if (!array_key_exists($interface, $mapping)) {
					throw new NotAnExtensionInterfaceException($className, $parameter->name, $interface);
				}

				$definition->setArgument($parameter->name, new Statement(LazyExtensionsCollection::class, [
					new Reference(Container::class),
					$interface,
				]));
			}
		}
	}

	/**
	 * @param class-string $className
	 * @param array<lowercase-string, non-empty-list<TargetMethodParameter<AutowiredParameter>>> $constructorParameters
	 */
	public static function processConstructorParameters(ContainerBuilder $builder, string $className, ServiceDefinition $definition, array $constructorParameters): void
	{
		foreach ($constructorParameters[strtolower($className)] ?? [] as $autowiredParameter) {
			$ref = $autowiredParameter->attribute->ref;
			if ($ref === null) {
				$argument = Helpers::expand(
					'%' . Helpers::escape($autowiredParameter->name) . '%',
					$builder->parameters,
				);
			} elseif (Strings::match($ref, '#^@[\w\\\\]+$#D') !== null) {
				$argument = new Reference(substr($ref, 1));
			} else {
				$argument = Helpers::expand(
					$ref,
					$builder->parameters,
				);
			}
			$definition->setArgument($autowiredParameter->name, $argument);
		}
	}

}
