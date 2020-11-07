<?php

namespace MatchExprRule;

class Foo
{

	/**
	 * @param 1|2|3 $i
	 */
	public function doFoo(int $i): void
	{
		match ($i) {
			'foo' => null, // always false
			default => null,
		};

		match ($i) {
			0 => null,
			1 => null,
			2 => null,
			3 => null, // always true, but do not report (it's the last one)
		};

		match ($i) {
			1 => null,
			2 => null,
			3 => null, // always true
			4 => null, // unreachable
		};

		match ($i) {
			1 => null,
			2 => null,
			3 => null, // always true
			default => null, // unreachable
		};

		match (1) {
			1 => null, // always true
			2 => null, // unreachable
			3 => null, // unreachable
		};

		match (1) {
			1 => null, // always true
			default => null, // unreachable
		};

		match ($i) {
			1, 2 => null,
			// unhandled
		};

		match ($i) {
			// unhandled
		};

		match ($i) {
			1, 2 => null,
			default => null, // OK
		};

		match ($i) {
			3, 3 => null, // second 3 is always false
			default => null,
		};

		match (1) {
			1 => 1, // always true - report
		};

		match ($i) {
			default => 1, // always true - report
		};

		match ($i) {
			default => 1, // always true - report
			1 => 2, // unreachable - report
		};
	}

}
