<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\Definitions\ServiceDefinition;
use Nette\Schema\Expect;
use PHPStan\Rules\RegistryFactory;

class RulesExtension extends \Nette\DI\CompilerExtension
{

	public function getConfigSchema(): \Nette\Schema\Schema
	{
		return Expect::listOf('string');
	}

	public function loadConfiguration(): void
	{
		/** @var mixed[] $config */
		$config = $this->config;
		$builder = $this->getContainerBuilder();

		foreach ($config as $key => $rule) {
			/** @var ServiceDefinition $definition */
			$definition = $builder->addDefinition($this->prefix((string) $key));
			$definition->setFactory($rule)
				->setAutowired(false)
				->addTag(RegistryFactory::RULE_TAG);
		}
	}

}
