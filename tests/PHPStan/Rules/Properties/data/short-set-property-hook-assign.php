<?php // lint >= 8.4

namespace ShortSetPropertyHookAssign;

class Foo
{

	public int $i {
		set => 'foo';
	}

	public int $i2 {
		set => 1;
	}

	/** @var non-empty-string */
	public string $s {
		set => '';
	}

	/** @var non-empty-string */
	public string $s2 {
		set => 'foo';
	}

}

/**
 * @template T of Foo
 */
class GenericFoo
{

	/** @var T */
	public Foo $a {
		set => new Foo();
	}

	/** @var T */
	public Foo $a2 {
		set => $this->a2;
	}

	/**
	 * @param T $c
	 */
	public function __construct(
		/** @var T */
		public Foo $b {
			set => new Foo();
		},

		/** @var T */
		public Foo $b2 {
			set => $this->b2;
		},

		public Foo $c {
			set => new Foo();
		},

		public Foo $c2 {
			set => $this->c2;
		}
	)
	{
	}

}
