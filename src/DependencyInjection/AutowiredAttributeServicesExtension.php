<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Override;
use PHPStan\DependencyInjection\AttributeServices\AttributeServicesRegistrar;
use PHPStan\DependencyInjection\AttributeServices\AttributeTargetsProvider;
use stdClass;
use function array_keys;

#[ContainerExtension(name: 'autowiredAttributeServices')]
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
		/** @var stdClass&object{level: int|null} $config */
		$config = $this->getConfig();
		$builder = $this->getContainerBuilder();
		$targets = AttributeTargetsProvider::create();
		$registeredClasses = AttributeServicesRegistrar::registerServices($builder, $targets, $config->level);

		// ResultCacheManager treats these like the classes from the services section of the config
		$discoveredClasses = [];
		foreach ($registeredClasses as $registeredClass) {
			if (!$targets->isDiscoveredClass($registeredClass)) {
				continue;
			}

			$discoveredClasses[$registeredClass] = true;
		}
		$builder->parameters['attributeServicesClasses'] = array_keys($discoveredClasses);
	}

}
