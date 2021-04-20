<?php

namespace NestedGenericIncompleteConstructor;

use function PHPStan\Testing\assertType;

/**
 * @template T
 * @template U of T
 */
class Foo
{

	/** @var T */
	public $t;

	/** @var U */
	public $u;

	/**
	 * @param T $t
	 */
	public function __construct($t)
	{

	}

}

function (): void {
	$foo = new Foo(1);
	//assertType('NestedGenericIncompleteConstructor\Foo<int, int>', $foo);
	assertType('int', $foo->t);
	assertType('int', $foo->u);
};
