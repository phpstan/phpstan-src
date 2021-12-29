<?php // lint >= 8.1

namespace TraitUseEnum;

enum FooEnum
{

}

class Foo
{

	use FooEnum;

}

function (): void {
	new class() {

		use FooEnum;

	};
};
