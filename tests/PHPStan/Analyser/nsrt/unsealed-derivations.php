<?php

namespace UnsealedDerivations;

use function PHPStan\Testing\assertType;

class FilterFalsey
{

	/**
	 * @param array{a: int, ...<string, int|null>} $arr
	 */
	public function filterUnsealed(array $arr): void
	{
		// `array_filter` drops falsey entries from both the explicit slot
		// and the unsealed extras. The unsealed value type must have the
		// falsey union (`null|false|0|0.0|''|'0'|[]`) subtracted too —
		// here `int|null` collapses to non-zero `int`.
		assertType(
			'array{a?: int<min, -1>|int<1, max>, ...<string, int<min, -1>|int<1, max>>}',
			array_filter($arr),
		);
	}

}

class ChangeKeyCase
{

	/**
	 * @param array{Foo: int, ...<string, float>} $arr
	 */
	public function lowerCaseUnsealed(array $arr): void
	{
		// `array_change_key_case` folds explicit constant-string keys.
		// The unsealed slot must be carried through — and the unsealed
		// key picks up the matching `lowercase-string` accessory (every
		// key after CASE_LOWER is lowercase).
		assertType(
			'array{foo: int, ...<lowercase-string, float>}',
			array_change_key_case($arr, CASE_LOWER),
		);
	}

	/**
	 * @param array{Foo: int, ...<string, float>} $arr
	 */
	public function upperCaseUnsealed(array $arr): void
	{
		assertType(
			'array{FOO: int, ...<uppercase-string, float>}',
			array_change_key_case($arr, CASE_UPPER),
		);
	}

	/**
	 * @param array{Foo: int, ...<int|string, float>} $arr
	 */
	public function mixedKeyUnsealed(array $arr): void
	{
		// Int keys aren't affected by `array_change_key_case`; only the
		// string portion of the unsealed key picks up the accessory.
		assertType(
			'array{foo: int, ...<int|lowercase-string, float>}',
			array_change_key_case($arr, CASE_LOWER),
		);
	}

	/**
	 * @param array{a: int, ...<lowercase-string, float>} $arr
	 */
	public function lowercaseToUpper(array $arr): void
	{
		// CASE_UPPER on a `lowercase-string` unsealed key drops the
		// lowercase property and replaces it with uppercase —
		// `array_change_key_case` rewrites every key, so the prior case
		// constraint no longer holds.
		assertType(
			'array{A: int, ...<uppercase-string, float>}',
			array_change_key_case($arr, CASE_UPPER),
		);
	}

	/**
	 * @param array{a: int, ...<non-empty-string, float>} $arr
	 */
	public function preserveNonEmpty(array $arr): void
	{
		// Case-folding keeps the string length unchanged, so non-empty
		// is preserved alongside the new case accessory on the unsealed
		// key.
		assertType(
			'array{a: int, ...<lowercase-string&non-empty-string, float>}',
			array_change_key_case($arr, CASE_LOWER),
		);
	}

	/**
	 * @param array{Foo: int, BAR: string, ...<string, float>} $arr
	 */
	public function multipleConstantKeys(array $arr): void
	{
		// Each `ConstantStringType` explicit key is independently folded.
		assertType(
			'array{foo: int, bar: string, ...<lowercase-string, float>}',
			array_change_key_case($arr, CASE_LOWER),
		);
	}

	/**
	 * @param array{Foo: int, foo: string} $arr
	 */
	public function collidingConstantKeys(array $arr): void
	{
		// `Foo` and `foo` both fold to `foo`. PHP semantics: the later
		// pair overwrites the earlier (the `foo: string` entry wins).
		assertType(
			'array{foo: string}',
			array_change_key_case($arr, CASE_LOWER),
		);
	}

	/**
	 * @param array{Foo: int} $arr
	 */
	public function unknownCase(array $arr, int $case): void
	{
		// Non-constant `$case` — could be either CASE_LOWER or CASE_UPPER.
		// `Foo` folds to `'foo'|'FOO'` and the builder splits the union
		// into two optional keys, with at least one guaranteed present.
		assertType(
			'non-empty-array{foo?: int, FOO?: int}',
			array_change_key_case($arr, $case),
		);
	}

}

class ArrayUnshift
{

	/**
	 * @param list{int, string, ...<float>} $arr
	 */
	public function prependPreservesUnsealed(array $arr): void
	{
		array_unshift($arr, true, null);
		// `array_unshift` prepends the new values and re-indexes; the
		// original list's unsealed tail (`...<float>`) must be carried
		// through so the result still tracks "extra entries are
		// `float`".
		assertType('array{true, null, int, string, ...<float>}', $arr);
	}

}

class ArrayFilterCallback
{

	/**
	 * @param array{a: int, ...<string, int|null>} $arr
	 */
	public function preserveUnsealed(array $arr): void
	{
		// `array_filter` with a callback narrows each entry by the
		// predicate's truthy projection. The unsealed slot must follow
		// the same narrowing — `int|null` minus `null` is `int`.
		assertType(
			'array{a: int, ...<string, int>}',
			array_filter($arr, fn ($v) => $v !== null),
		);
	}

}

class ArrayColumn
{

	/**
	 * @param list{array{name: string, age: int}, array{name: string, age: int}, ...<array{name: string, age: int}>} $rows
	 */
	public function preserveUnsealed(array $rows): void
	{
		// `array_column` plucks the named field from every row,
		// including rows from the unsealed tail. Each row's `name`
		// is `string`, so the unsealed slot of the result is `string`
		// at the original integer keys.
		assertType(
			'array{string, string, ...<string>}',
			array_column($rows, 'name'),
		);
	}

}

class FilterVarArray
{

	/**
	 * @param array{a: int, ...<string, mixed>} $arr
	 */
	public function preserveUnsealed(array $arr): void
	{
		// `filter_var_array` applies the filter to every value,
		// including the unsealed extras. The unsealed value type
		// becomes the filter's projected output (`int|false` for
		// `FILTER_VALIDATE_INT` over `mixed`).
		assertType(
			'array{a: int, ...<string, int|false>}',
			filter_var_array($arr, FILTER_VALIDATE_INT),
		);
	}

}

class ArrayMerge
{

	/**
	 * @param array{a: int, ...<string, float>} $arr
	 */
	public function mergePreservesUnsealed(array $arr): void
	{
		// `array_merge` with a sealed second arg appends `b` and keeps
		// the unsealed extras from the first array.
		assertType(
			'array{a: int, b: true, ...<string, float>}',
			array_merge($arr, ['b' => true]),
		);
	}

}

class ArrayReplace
{

	/**
	 * @param array{a: int, ...<string, float>} $arr
	 */
	public function replacePreservesUnsealed(array $arr): void
	{
		// `array_replace` overwrites by key, but the unsealed extras
		// from `$arr` survive at any unmentioned keys.
		assertType(
			'array{a: int, b: true, ...<string, float>}',
			array_replace($arr, ['b' => true]),
		);
	}

}

class CountNarrowing
{

	/**
	 * @param list{int, string, ...<float>} $arr
	 */
	public function geMinPreservesUnsealed(array $arr): void
	{
		if (count($arr) >= 5) {
			// `count >= 5` guarantees the first 5 entries exist (the
			// explicit prefix `[int, string]` plus three values from the
			// unsealed `<float>` range). Beyond five, the unsealed slot
			// is preserved so further entries can still appear.
			assertType('array{int, string, float, float, float, ...<float>}', $arr);
		}
	}

}
