<?php declare(strict_types = 1);

namespace UnionIntersectionMethodVariants;

interface AcceptsInt {
	public function process(int $x): string;
}

interface AcceptsString {
	public function process(string $x): string;
}

interface TwoParams {
	public function transform(int $x, string $y): void;
}

interface OneParam {
	public function transform(int $x): void;
}

interface ReturnsInt {
	public function compute(int $x): int;
}

interface ReturnsString {
	public function compute(int $x): string;
}

class IntersectionTests
{
	/**
	 * Intersection type: object IS both AcceptsInt and AcceptsString.
	 * Implementation must handle both int and string params.
	 * So calling with int OR string should both be accepted.
	 *
	 * @param AcceptsInt&AcceptsString $obj
	 */
	public function testIntersectionParamTypes($obj): void
	{
		$obj->process(42);       // OK - int satisfies AcceptsInt
		$obj->process('hello');  // OK - string satisfies AcceptsString
		$obj->process(true);     // ERROR - bool doesn't satisfy either
	}

	/**
	 * Intersection with different param counts.
	 * TwoParams needs (int, string), OneParam needs (int).
	 * Implementation must handle both, so it has (int $x, string $y = optional).
	 *
	 * @param TwoParams&OneParam $obj
	 */
	public function testIntersectionParamCount($obj): void
	{
		$obj->transform(42, 'hello'); // OK - satisfies TwoParams
		$obj->transform(42);          // OK - satisfies OneParam
		$obj->transform();            // ERROR - both require at least 1 param
	}
}

class UnionTests
{
	/**
	 * Union type: object is EITHER AcceptsInt or AcceptsString.
	 * combineAcceptors unions params: int|string, so both calls accepted.
	 *
	 * @param AcceptsInt|AcceptsString $obj
	 */
	public function testUnionParamTypes($obj): void
	{
		$obj->process(42);       // OK - pragmatic (valid for AcceptsInt)
		$obj->process('hello');  // OK - pragmatic (valid for AcceptsString)
		$obj->process(true);     // ERROR - bool not in int|string
	}

	/**
	 * Union with different param counts.
	 *
	 * @param TwoParams|OneParam $obj
	 */
	public function testUnionParamCount($obj): void
	{
		$obj->transform(42, 'hello'); // OK
		$obj->transform(42);          // OK - OneParam only needs 1
		$obj->transform();            // ERROR - both need at least 1
	}
}
