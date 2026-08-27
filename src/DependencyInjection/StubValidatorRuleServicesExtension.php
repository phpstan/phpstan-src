<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Override;
use PHPStan\DependencyInjection\AttributeServices\AttributeServicesRegistrar;
use PHPStan\DependencyInjection\AttributeServices\AttributeTargetsProvider;
use PHPStan\PhpDoc\StubValidator;

final class StubValidatorRuleServicesExtension extends CompilerExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		$builder = $this->getContainerBuilder();
		$targets = AttributeTargetsProvider::create();
		$constructorParameters = AttributeServicesRegistrar::collectConstructorParameters($targets);

		foreach ($targets->findTargetClasses(ValidatesStubFiles::class) as $class) {
			$definition = $builder->addDefinition(null)
				->setFactory($class->name)
				->setAutowired(false)
				->addTag(StubValidator::SERVICE_RULE_TAG);

			AttributeServicesRegistrar::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
		}
	}

}
