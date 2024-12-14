<?php // lint >= 8.4

namespace PropertyHooksReturn;

class Foo
{

	public int $i {
		get {
			if (rand(0, 1)) {
				return 'foo';
			}

			return 1;
		}
		set {
			if (rand(0, 1)) {
				return;
			}

			return 1;
		}
	}

	/** @var non-empty-string */
	public string $s {
		get {
			if (rand(0, 1)) {
				return '';
			}

			return 'foo';
		}
	}

}

/**
 * @template T of Foo
 */
class GenericFoo
{

	/** @var T */
	public Foo $a {
		get {
			if (rand(0, 1)) {
				return new Foo();
			}

			return $this->a;
		}
	}

	/**
	 * @param T $c
	 */
	public function __construct(
		/** @var T */
		public Foo $b {
			get {
				if (rand(0, 1)) {
					return new Foo();
				}

				return $this->b;
			}
		},

		public Foo $c {
			get {
				if (rand(0, 1)) {
					return new Foo();
				}

				return $this->c;
			}
		}
	)
	{
	}

}
