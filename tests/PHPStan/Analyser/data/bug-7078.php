<?php // lint >= 8.0

namespace Bug7078;

use function PHPStan\Testing\assertType;

/**
 * @template-covariant T
 */
final class TypeDefault
{
	/** @param T $defaultValue */
	public function __construct(private mixed $defaultValue)
	{
	}

	/** @return T */
	public function parse(): mixed
	{
		return $this->defaultValue;
	}
}

interface Param {
	/**
	 * @param TypeDefault<T> $type
	 *
	 * @template T
	 * @return T
	 */
	public function get(TypeDefault ...$type);
}

function (Param $p) {
	$result = $p->get(new TypeDefault(1), new TypeDefault('a'));
	assertType('1|\'a\'', $result);
};
