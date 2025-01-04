<?php // lint >= 8.4

namespace ShortGetPropertyHookReturn;

class Foo
{

	public int $i {
		get => 'foo';
	}

	public int $i2 {
		get => 1;
	}

	/** @var non-empty-string */
	public string $s {
		get => '';
	}

	/** @var non-empty-string */
	public string $s2 {
		get => 'foo';
	}

}

/**
 * @template T of Foo
 */
class GenericFoo
{

	/** @var T */
	public Foo $a {
		get => new Foo();
	}

	/** @var T */
	public Foo $a2 {
		get => $this->a2;
	}

	/**
	 * @param T $c
	 */
	public function __construct(
		/** @var T */
		public Foo $b {
			get => new Foo();
		},

		/** @var T */
		public Foo $b2 {
			get => $this->b2;
		},

		public Foo $c {
			get => new Foo();
		},

		public Foo $c2 {
			get => $this->c2;
		}
	)
	{
	}

}
