<?php

namespace PropertyVariance;

/** @template-contravariant T */
interface In {
}

/** @template-covariant T */
interface Out {
}

/** @template T */
interface Invariant {
}

/**
 * @template X
 */
class A {
	/** @var X */
	public $a;

	/** @var In<X> */
	public $b;

	/** @var Out<X> */
	public $c;

	/** @var Invariant<X> */
	public $d;

	/** @var X */
	private $e;

	/** @var In<X> */
	private $f;

	/** @var Out<X> */
	private $g;

	/** @var Invariant<X> */
	private $h;
}

/**
 * @template-covariant X
 */
class B {
	/** @var X */
	public $a;

	/** @var In<X> */
	public $b;

	/** @var Out<X> */
	public $c;

	/** @var Invariant<X> */
	public $d;

	/** @var X */
	private $e;

	/** @var In<X> */
	private $f;

	/** @var Out<X> */
	private $g;

	/** @var Invariant<X> */
	private $h;
}

/**
 * @template-contravariant X
 */
class C {
	/** @var X */
	public $a;

	/** @var In<X> */
	public $b;

	/** @var Out<X> */
	public $c;

	/** @var Invariant<X> */
	public $d;

	/** @var X */
	private $e;

	/** @var In<X> */
	private $f;

	/** @var Out<X> */
	private $g;

	/** @var Invariant<X> */
	private $h;
}
