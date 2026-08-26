<?php // lint >= 8.0

namespace AppAttribute;

use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\ContainerExtension;
use PHPStan\DependencyInjection\GenerateFactory;
use PHPStan\DependencyInjection\NonAutowiredService;
use PHPStan\DependencyInjection\RegisteredCollector;
use PHPStan\DependencyInjection\RegisteredRule;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Rules\Rule;

#[AutowiredService]
class MyService
{

	public function __construct(
		#[AutowiredParameter(ref: '%tmpDir%')]
		private string $tmpDir,
		#[AutowiredExtensions(of: Rule::class)]
		private mixed $rules,
	)
	{
	}

}

#[NonAutowiredService(name: 'appAttribute.service')]
class MyNamedService
{

}

#[RegisteredRule(level: 0)]
class MyRule
{

}

#[RegisteredCollector(level: 5)]
class MyCollector
{

}

interface MyFactory
{

}

#[GenerateFactory(interface: MyFactory::class)]
class MyResult
{

}

#[ContainerExtension(name: 'appAttribute')]
class MyExtension
{

}

#[ValidatesStubFiles]
class MyStubRule
{

}

#[\Attribute]
class MyCustomAttribute
{

}

#[MyCustomAttribute]
class UsesCustom
{

}
