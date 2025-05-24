<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use olvlvl\ComposerAttributeCollector\Attributes;

final class AutowiredAttributeServicesExtension extends CompilerExtension
{

	public function loadConfiguration(): void
	{
		require_once __DIR__ . '/../../vendor/attributes.php';
		$autowiredServiceClasses = Attributes::findTargetClasses(AutowiredService::class);
		$builder = $this->getContainerBuilder();

		foreach ($autowiredServiceClasses as $class) {
			$builder->addDefinition(null)
				->setType($class->name)
				->setAutowired();
		}
	}

}
