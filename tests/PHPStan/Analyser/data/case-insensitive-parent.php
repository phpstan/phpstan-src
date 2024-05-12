<?php

namespace CaseInsensitiveParent;

use function PHPStan\Testing\assertType;

class A {
	public function doFoo():string {
		return "hello";
	}

}

class B extends A {
	public function doFoo():string {
		assertType('string', PARENT::doFoo());
		assertType('string', parent::doFoo());

		assertType('class-string', PARENT::class);

		return PARENT::doFoo();
	}
}
