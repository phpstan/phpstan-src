<?php // lint >= 8.0

namespace ConditionalTypeForParameterTemplate;

use stdClass;
use function PHPStan\Testing\assertType;

class A
{

	/**
	 * @template T
	 * @param T $result
	 * @return ($result is false ? never : T)
	 */
	public function throwOnFailure($result)
	{
		if ($result === false) {
			throw new \Exception();
		}

		return $result;
	}

	/**
	 * @template T
	 * @param T $result
	 * @return ($result is not false ? T : never)
	 */
	public function negated($result)
	{
		if ($result === false) {
			throw new \Exception();
		}

		return $result;
	}

	/**
	 * @template T
	 * @param T $a
	 * @param T $b
	 * @return ($a is false ? never : T)
	 */
	public function boundByTwoParameters($a, $b)
	{
		throw new \Exception();
	}

	/**
	 * @template T
	 * @param T $a
	 * @param list<T> $others
	 * @return ($a is false ? never : T)
	 */
	public function boundByAnotherParameter($a, array $others)
	{
		throw new \Exception();
	}

	/**
	 * @template T
	 * @param T|null $value
	 * @return ($value is null ? never : T)
	 */
	public function nullable($value)
	{
		throw new \Exception();
	}

	/**
	 * @template T
	 * @param T $val
	 * @return ($val is array ? T : null)
	 */
	public function validateArray(mixed $val): ?array
	{
		return \is_array($val) ? $val : null;
	}

}

/**
 * @template T
 */
class Generic
{

	/**
	 * @param T $x
	 * @return ($x is null ? never : T)
	 */
	public function classTemplate($x)
	{
		throw new \Exception();
	}

}

/**
 * @template T
 * @param T $result
 * @return ($result is false ? never : T)
 */
function throwOnFailure($result)
{
	if ($result === false) {
		throw new \Exception();
	}

	return $result;
}

/**
 * @param stdClass|false $v
 * @param array{a: int}|string $as
 * @param Generic<stdClass|null> $g
 */
function test(A $a, $v, $as, mixed $m, Generic $g, ?stdClass $n): void
{
	assertType('stdClass', $a->throwOnFailure($v));
	assertType('stdClass', throwOnFailure($v));
	assertType('stdClass', $a->negated($v));
	assertType("'str'|stdClass|false", $a->boundByTwoParameters($v, 'str'));
	assertType('stdClass|false', $a->boundByAnotherParameter($v, []));
	assertType('stdClass', $a->nullable($n));
	assertType('array|null', $a->validateArray($as));
	assertType('stdClass|null', $g->classTemplate($n));

	foreach ($a->validateArray($m) ?? [] as $x) {
		assertType('mixed', $x);
		assertType('array|null', $a->validateArray($x));
	}
}
