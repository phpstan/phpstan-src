<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Override;
use PHPStan\DependencyInjection\AttributeServices\AttributeServicesRegistrar;
use PHPStan\DependencyInjection\AttributeServices\AttributeTargetsProvider;
use stdClass;

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
		AttributeServicesRegistrar::registerServices(
			$this->getContainerBuilder(),
			AttributeTargetsProvider::create(),
			$config->level,
		);
	}

}
