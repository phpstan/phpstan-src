<?php // lint >= 8.1

namespace ClassExtendsEnum;

enum FooEnum
{

}

class Foo extends FooEnum
{

}

function (): void {
	new class() extends FooEnum {

	};
};
