<?php declare(strict_types = 1);

namespace Bug14504Method;

/**
 * @template T of int|string
 */
class Foo
{

	/** @param T $val */
	public function __construct(private $val) {}

	/** @phpstan-pure */
	public function toString(): string {
		return (string)$this->val;
	}

}
