<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use olvlvl\ComposerAttributeCollector\Attributes;
use Override;
use PHPStan\PhpDoc\StubValidator;
use function array_merge;
use function strcasecmp;
use function strtolower;

final class StubValidatorRuleServicesExtension extends CompilerExtension
{

	#[Override]
	public function loadConfiguration(): void
	{
		require_once __DIR__ . '/../../vendor/attributes.php';
		$builder = $this->getContainerBuilder();
		$discoverer = AutowiredServiceDiscoverer::createFromContainerBuilder($builder);

		$autowiredParameters = array_merge(
			Attributes::findTargetMethodParameters(AutowiredParameter::class),
			$discoverer->findTargetMethodParameters(AutowiredParameter::class),
		);
		$constructorParameters = [];
		foreach ($autowiredParameters as $parameter) {
			if (strcasecmp($parameter->method, '__construct') !== 0) {
				continue;
			}
			$lowerClass = strtolower($parameter->class);
			$constructorParameters[$lowerClass] ??= [];
			$constructorParameters[$lowerClass][] = $parameter;
		}

		foreach (array_merge(Attributes::findTargetClasses(ValidatesStubFiles::class), $discoverer->findTargetClasses(ValidatesStubFiles::class)) as $class) {
			$definition = $builder->addDefinition(null)
				->setFactory($class->name)
				->setAutowired(false)
				->addTag(StubValidator::SERVICE_RULE_TAG);

			AutowiredAttributeServicesExtension::processConstructorParameters($builder, $class->name, $definition, $constructorParameters);
		}
	}

}
