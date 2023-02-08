<?php declare(strict_types = 1);

namespace TraitInstanceOf;

use function PHPStan\Testing\assertType;

trait Trait1 {
	public function test(): string {
		assertType('$this(TraitInstanceOf\ATrait1Class)', $this);
		if ($this instanceof WithoutFoo) {
			assertType('$this(TraitInstanceOf\ATrait1Class)&TraitInstanceOf\WithoutFoo', $this);
			return 'hello world';
		}

		if ($this instanceof FinalOther) {
			assertType('*NEVER*', $this);
			return 'hello world';
		}

		assertType('$this(TraitInstanceOf\ATrait1Class)', $this);
		if ($this instanceof Trait2) {
			assertType('*NEVER*', $this);
			return 'hello world';
		}

		if ($this instanceof FinalTrait2Class) {
			assertType('*NEVER*', $this);
			return 'hello world';
		}

		assertType('$this(TraitInstanceOf\ATrait1Class)', $this);
		throw new \Error();
	}
}

trait Trait2 {
	public function test(): string {
		assertType('$this(TraitInstanceOf\FinalTrait2Class)', $this);

		if ($this instanceof FinalTrait2Class) {
			assertType('$this(TraitInstanceOf\FinalTrait2Class)&TraitInstanceOf\FinalTrait2Class', $this);
			return 'hello world';
		}

		if ($this instanceof ATrait1Class) {
			assertType('$this(TraitInstanceOf\FinalTrait2Class~TraitInstanceOf\FinalTrait2Class)&TraitInstanceOf\ATrait1Class', $this);
			return 'hello world';
		}

		if ($this instanceof FinalOther) {
			assertType('*NEVER*', $this);
			return 'hello world';
		}

		return 'hello world';
	}
}

final class FinalOther {
}

final class FinalTrait2Class {
	use Trait2;
}

class WithoutFoo {}

class ATrait1Class {
	use Trait1;
}
