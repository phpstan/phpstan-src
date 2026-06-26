<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14844Siblings;

enum Someenum: string
{
	case FOO = 'foo';
	case BAR = 'bar';
}

/**
 * @template TReturn
 * @param callable(): TReturn $callable
 * @return TReturn
 */
function doFoo(callable $callable)
{
	return $callable();
}

/**
 * @template T
 * @param T $x
 * @return T
 */
function identity($x)
{
	return $x;
}

/**
 * A sealed constant array carries a `[never, never]` unsealed sentinel under
 * bleeding edge. Every operation below transforms or passes through that
 * sentinel via a different ConstantArrayType method (mapValueType, traverse,
 * traverseSimultaneously, generalizeValues, ...). None of them may leak an
 * ErrorType into the sentinel slot, which UnresolvableTypeHelper would flag as
 * a bogus "contains unresolvable type" error on these generic calls.
 */
class A
{

	public function arrayMap(): void
	{
		// mapValueType
		doFoo(fn () => array_map(fn (Someenum $type) => $type->value, Someenum::cases()));
	}

	public function nestedArrayMap(): void
	{
		// mapValueType applied to a mapped array
		doFoo(fn () => array_map(
			static fn (string $v) => strtoupper($v),
			array_map(fn (Someenum $type) => $type->value, Someenum::cases()),
		));
	}

	public function strReplace(): void
	{
		// ReplaceFunctionsDynamicReturnTypeExtension -> mapValueType
		doFoo(fn () => str_replace('f', 'F', array_map(fn (Someenum $type) => $type->value, Someenum::cases())));
	}

	public function genericIdentity(): void
	{
		// template resolution / traverse over a sealed array passed by value
		identity(array_map(fn (Someenum $type) => $type->value, Someenum::cases()));
	}

	public function spread(): void
	{
		doFoo(fn () => [...array_map(fn (Someenum $type) => $type->value, Someenum::cases())]);
	}

	public function arrayUnion(): void
	{
		// unionArrays
		$mapped = array_map(fn (Someenum $type) => $type->value, Someenum::cases());
		doFoo(fn () => $mapped + $mapped);
	}

	public function arrayReverse(): void
	{
		// reverseArray
		identity(array_reverse(array_map(fn (Someenum $type) => $type->value, Someenum::cases())));
	}

	public function arrayValues(): void
	{
		// getValuesArray
		identity(array_values(array_map(fn (Someenum $type) => $type->value, Someenum::cases())));
	}

	public function arrayFilter(): void
	{
		// filterArrayRemovingFalsey
		identity(array_filter(array_map(fn (Someenum $type) => $type->value, Someenum::cases())));
	}

	public function arrayMerge(): void
	{
		// mergeArrays
		$mapped = array_map(fn (Someenum $type) => $type->value, Someenum::cases());
		identity(array_merge($mapped, $mapped));
	}

	public function plainSealedArray(): void
	{
		// a plain sealed array literal also carries the sentinel
		identity(['foo', 'bar']);
		identity(array_reverse(['foo', 'bar']));
		identity(array_values(['foo', 'bar']));
		identity(array_change_key_case(['foo' => 1, 'bar' => 2]));
	}

}
