<?php // lint >= 8.1

namespace ClassImplementsEnum;

enum FooEnum
{

}

class Foo implements FooEnum
{

}

function (): void {
	new class() implements FooEnum {

	};
};
