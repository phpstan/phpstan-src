<?php // lint >= 8.0

namespace PHPStan\Fixture\ApiAttribute;

use PHPStan\DependencyInjection\AutowiredExtensions;
use PHPStan\DependencyInjection\ContainerExtension;
use PHPStan\DependencyInjection\ValidatesStubFiles;
use PHPStan\Rules\Rule;

#[ContainerExtension(name: 'inPhpStan')]
class MyExtension
{

}

#[ValidatesStubFiles]
class MyStubRule
{

	public function __construct(
		#[AutowiredExtensions(of: Rule::class)]
		private mixed $rules,
	)
	{
	}

}
