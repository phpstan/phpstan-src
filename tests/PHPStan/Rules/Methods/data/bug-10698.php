<?php declare(strict_types = 1);

namespace Bug10698;

/** @template T */
class Foo {
	/** @param T $subject */
	public function __construct(private $subject) {}

	/** @return T */
	public function getSubject() {
		return $this->subject;
	}
}

class Bar {
	/**
	 * @template T
	 * @param Foo<T> $foo
	 * @return T
	 */
	public function qux(Foo $foo) {
		return $foo->getSubject();
	}
}

function x(?string $str): void {
	$bar = new Bar();
	$bar->qux(new Foo($str));
}
