<?php

namespace MethodPhpDocsNamespace;

use function PHPStan\Testing\assertType;

use SomeNamespace\Amet as Dolor;
use SomeNamespace\Consecteur;

class FooWithTrait extends FooParent
{

	use FooTrait;

	/**
	 * @return Bar
	 */
	public static function doSomethingStatic()
	{

	}

	/**
	 * @return self[]
	 */
	public function doBar(): array
	{

	}

	public function returnParent(): parent
	{

	}

	/**
	 * @return parent
	 */
	public function returnPhpDocParent()
	{

	}

	/**
	 * @return NULL[]
	 */
	public function returnNulls(): array
	{

	}

	public function returnObject(): object
	{

	}

	public function phpDocVoidMethod(): self
	{

	}

	public function phpDocVoidMethodFromInterface(): self
	{

	}

	public function phpDocVoidParentMethod(): self
	{

	}

	public function phpDocWithoutCurlyBracesVoidParentMethod(): self
	{

	}

	/**
	 * @return string[]
	 */
	public function returnsStringArray(): array
	{

	}

	private function privateMethodWithPhpDoc()
	{

	}

}

function (FooWithTrait $foo): void {
	$parent = new FooParent();
	assertType('MethodPhpDocsNamespace\FooParent', $parent->doLorem());
	assertType('MethodPhpDocsNamespace\FooParent', $parent->doIpsum());
	assertType('MethodPhpDocsNamespace\FooParent', $foo->returnParent());
	assertType('MethodPhpDocsNamespace\FooParent', $foo->returnPhpDocParent());
};
