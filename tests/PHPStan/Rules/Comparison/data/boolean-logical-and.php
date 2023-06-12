<?php

namespace ConstantCondition\Logical;

class BooleanAnd
{

	public function doFoo(int $i, bool $j, \stdClass $std, ?\stdClass $nullableStd)
	{
		if ($i and $j) {

		}

		$one = 1;
		if ($one and $i) {

		}

		if ($i and $std) {

		}

		$zero = 0;
		if ($zero and $i) {

		}
		if ($i and $zero) {

		}
		if ($one === 0 and $one) {

		}
		if ($one === 1 and $one) {

		}
		if ($nullableStd and $nullableStd) {

		}
		if ($nullableStd !== null and $nullableStd) {

		}
	}

	/**
	 * @param Foo|Bar $union
	 * @param Lorem&Ipsum $intersection
	 */
	public function checkUnionAndIntersection($union, $intersection)
	{
		if ($union instanceof Foo and $union instanceof Bar) {

		}

		if ($intersection instanceof Lorem and $intersection instanceof Ipsum) {

		}

		if ($union instanceof Foo || $union instanceof Bar) {

		} elseif ($union instanceof Foo and doFoo()) {

		}

		if ($intersection instanceof Lorem and $intersection instanceof Ipsum) {

		} elseif ($intersection instanceof Lorem and doFoo()) {

		}
	}

}

class NonNullablePropertiesShouldNotReportError
{

	/** @var self */
	private $foo;

	/** @var self */
	private $bar;

	public function doFoo()
	{
		if ($this->foo !== null and $this->bar !== null) {

		}
	}

}

class StringInIsset
{

	public function doFoo(string $s, string $t)
	{
		if (isset($s[1]) and isset($t[1])) {

		}
	}

}

class IssetBug
{

	public function doFoo(string $alias, array $options = [])
	{
		list($name, $p) = explode('.', $alias);
		if (isset($options['c']) and !\strpos($options['c'], '\\')) {
			// ...
		}

		if (!isset($options['c']) and \strpos($p, 'X') === 0) {
			// ?
		}
	}

}

class IntegerRangeType
{

	public function doFoo(int $i, float $f)
	{
		if ($i < 3 and $i > 5) { // can never happen
		}

		if ($f > 0 and $f < 1) {
		}
	}

}

class AndInIfCondition
{
	public function andInIfCondition($mixed, int $i): void
	{
		if (!$mixed) {
			if ($mixed and $i) {
			}
			if ($i and $mixed) {
			}
		}
		if ($mixed) {
			if ($mixed and $i) {
			}
			if ($i and $mixed) {
			}
		}
	}
}

function getMaybeArray() : ?array {
	if (rand(0, 1)) { return [1, 2, 3]; }
	return null;
}

function bug1924() {
	$arr = [
		'a' => getMaybeArray(),
		'b' => getMaybeArray(),
	];

	if (isset($arr['a']) and isset($arr['b'])) {
	}
}
