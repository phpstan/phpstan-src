<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection;

use Nette\DI\CompilerExtension;
use Nette\Schema\Expect;
use Nette\Schema\Schema;
use Override;
use PHPStan\Rules\LazyRegistry;

final class RulesExtension extends CompilerExtension
{

	#[Override]
	public function getConfigSchema(): Schema
	{
		return Expect::listOf('string');
	}

	#[Override]
	public function loadConfiguration(): void
	{
		/** @var mixed[] $config */
		$config = $this->config;
		$builder = $this->getContainerBuilder();

		foreach ($config as $key => $rule) {
			$builder->addDefinition($this->prefix((string) $key))
				->setFactory($rule)
				->setAutowired($rule)
				->addTag(LazyRegistry::RULE_TAG);
		}
	}

}
