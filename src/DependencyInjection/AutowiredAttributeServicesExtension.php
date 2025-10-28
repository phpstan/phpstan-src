<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
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

			$this->processConstructorParameters($class->name, $definition, $constructorParameters);

			foreach (ValidateServiceTagsExtension::INTERFACE_TAG_MAPPING as $interface => $tag) {
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

			$this->processConstructorParameters($class->name, $definition, $constructorParameters);
		}

		foreach (Attributes::findTargetClasses(GenerateFactory::class) as $class) {
			$attribute = $class->attribute;
			$definition = $builder->addFactoryDefinition(null)
				->setImplement($attribute->interface);

			if ($attribute->resultType !== null) {
				$definition->getResultDefinition()->setType($attribute->resultType);
			}

			$resultDefinition = $definition->getResultDefinition();
			$this->processConstructorParameters($class->name, $resultDefinition, $constructorParameters);
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

			$this->processConstructorParameters($class->name, $definition, $constructorParameters);
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

			$this->processConstructorParameters($class->name, $definition, $constructorParameters);
		}
	}

	/**
	 * @param class-string $className
	 * @param array<lowercase-string, non-empty-list<TargetMethodParameter<AutowiredParameter>>> $constructorParameters
	 */
	private function processConstructorParameters(string $className, ServiceDefinition $definition, array $constructorParameters): void
	{
		$builder = $this->getContainerBuilder();
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
