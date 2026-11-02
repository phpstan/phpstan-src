<?php // lint >= 8.1

namespace ApiAttributes;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Conditional;
use PHPStan\DependencyInjection\ContainerExtension;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\DependencyInjection\NonAutowiredService;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\DependencyInjection\RegisteredRule;

#[AutowiredService]
class Service
{

	public function __construct(
		#[AutowiredParameter(ref: '%tmpDir%')]
		private string $tmpDir,
	)
	{
	}

}

#[RegisteredRule(level: 0)]
class Rule
{

}

#[RegisteredCollector(level: 0)]
class Collector
{

}

#[GenerateFactory(interface: Factory::class)]
class Product
{

}

interface Factory
{

	public function create(): Product;

}

#[Conditional(attribute: new RegisteredRule(level: 4), if: '%featureToggles.foo%')]
class ConditionalRule
{

}

#[NonAutowiredService(name: 'foo')]
class NamedService
{

}

#[ContainerExtension(name: 'foo')]
class Extension
{

}
