<?php declare(strict_types = 1);

namespace PHPStan\Type;

/**
 * @api
 */
interface TypeTraverserCallable
{

	/**
	 * @param callable(Type): Type $traverse
	 */
	public function traverse(Type $type, callable $traverse): Type;

}
