<?php declare(strict_types = 1);

namespace Pr2465;

/** @template T */
class InvariantThing {}

/**
 * @template-covariant T
 */
class UnitOfTest
{
	/**
	 * @param InvariantThing<array<T>> $thing
	 */
	public function foo(InvariantThing $thing): void {}
}
