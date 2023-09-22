<?php declare(strict_types = 1);

namespace Bug9860;

use function PHPStan\Testing\assertType;

class A {}
class B {}
class C {}

class ANode {}
class BNode {}
class CNode {}

class HelloWorld
{
	public function a(): A|B {
		return new A();
	}

	/**
	 * @return ($b is A ? ANode : ($b is B ? BNode : CNode))
	 */
	public function b(A|B|C $b): ANode|BNode|CNode {
		return match(true) {
			$b instanceof A => new ANode(),
			$b instanceof B => new BNode(),
			default => new CNode(),
		};
	}

	public function test(): void {
		assertType('Bug9860\\ANode', $this->b(new A()));
		assertType('Bug9860\\BNode', $this->b(new B()));
		assertType('Bug9860\\ANode|Bug9860\\BNode', $this->b($this->a()));
	}
}
