<?php // lint >= 8.4

namespace TooWideThrowsPropertyHook;

use DomainException;

class Foo
{

	public int $a {
		/** @throws \InvalidArgumentException */
		get {
			throw new \InvalidArgumentException();
		}
	}

	public int $b {
		/** @throws \LogicException */
		get {
			throw new \InvalidArgumentException();
		}
	}

	public int $c {
		/** @throws \InvalidArgumentException */
		get {
			throw new \LogicException();
		}
	}

	public int $d {
		/** @throws \InvalidArgumentException|\DomainException */
		get { // error - DomainException unused
			throw new \InvalidArgumentException();
		}
	}

	public int $e {
		/** @throws void */
		get { // ok - picked up by different rule
			throw new \InvalidArgumentException();
		}
	}

	public int $f {
		/** @throws \InvalidArgumentException|\DomainException */
		get {
			if (rand(0, 1)) {
				throw new \InvalidArgumentException();
			}

			throw new DomainException();
		}
	}

	public int $g {
		/** @throws \DomainException */
		get { // error - DomainException unused
			throw new \InvalidArgumentException();
		}
	}

	public int $h {
		/**
		 * @throws \InvalidArgumentException
		 * @throws \DomainException
		 */
		get { // error - DomainException unused
			throw new \InvalidArgumentException();
		}
	}


	public int $j {
		/** @throws \DomainException */
		get { // error - DomainException unused

		}
	}

}
