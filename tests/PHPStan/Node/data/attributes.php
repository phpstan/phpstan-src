<?php // lint >= 8.0

namespace NodeCallbackCalled;

use ClassAttributes\AttributeWithConstructor;
use FunctionAttributes\Baz;

#[\Attribute(flags: \Attribute::TARGET_ALL)]
class UniversalAttribute
{
	public function __construct(int $foo)
	{
	}
}

#[UniversalAttribute(1)]
class MyClass
{

	#[UniversalAttribute(2)]
	private const MY_CONST = 'const';

	#[UniversalAttribute(3)]
	private string $myProperty;

	#[UniversalAttribute(4)]
	public function myMethod(#[UniversalAttribute(5)] string $arg): void
	{

	}

}

#[UniversalAttribute(6)]
interface MyInterface {}

#[UniversalAttribute(7)]
trait MyTrait {}

#[UniversalAttribute(8)]
function myFunction() {}
