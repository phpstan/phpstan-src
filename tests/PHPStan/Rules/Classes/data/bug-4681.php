<?php // lint >= 8.0

namespace Bug4681;

class A {
	/** @param array{a: int} $row */
	public function __construct(
		public array $row
	) {}
}

function (): void {
	/** @var list<array{a: int}> */
	$rows = [];

	$entries = array_map(
		static fn (array $row) : A => new A($row),
		$rows
	);
};

function (): void {
	/** @var list<array{a: int}> */
	$rows = [];

	$entries = array_map(
		/** @param array{a: int} $row */
		static fn (array $row) : A => new A($row),
		$rows
	);
};
