<?php declare(strict_types=1);

namespace PHPStan\Type\Test\D;

/** @template T */
interface Invariant {
	/** @return T */
	public function get();
}

/** @template-contravariant T */
interface In {
	/** @param T $a */
	public function consume($a);
}


/** @template-covariant T */
interface Out {
	/** @return T */
	public function get();
}
