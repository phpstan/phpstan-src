<?php // lint >= 8.0

namespace AttributeReflectionTest;

use Attribute;

#[Attribute]
class MyAttr
{

	public function __construct($one, $two)
	{

	}

}

#[MyAttr(1, 2)]
class Foo
{

	#[MyAttr(one: 3, two: 4)]
	public const MY_CONST = 1;

	#[MyAttr(two: 6, one: 5)]
	private $prop;

	#[MyAttr(7, 8)]
	public function __construct(
		#[MyAttr(9, 10)]
		int $test
	)
	{

	}

}

#[MyAttr()]
function myFunction() {

}

#[Nonexistent()]
function myFunction2() {

}

#[Nonexistent(1, 2)]
function myFunction3() {

}

#[MyAttr(11, 12)]
function myFunction4() {

}

#[MyAttr(28, two: 29)]
function myFunction5() {

}

#[Attribute(Attribute::TARGET_METHOD)]
class SelfReferencingAttr
{
	#[SelfReferencingAttr('hi')]
	public function __construct(string $param) {}
}

#[Attribute(Attribute::TARGET_ALL)]
class MutualAttrA
{
	#[MutualAttrB('world')]
	public function __construct(string $param) {}
}

#[Attribute(Attribute::TARGET_ALL)]
class MutualAttrB
{
	#[MutualAttrA('hello')]
	public function __construct(string $param) {}
}

#[Attribute(Attribute::TARGET_ALL)]
class SelfRefOnParam
{
	public function __construct(#[SelfRefOnParam('hello')] string $param) {}
}
