<?php // lint >= 8.3

declare(strict_types = 1);

namespace Bug13453;

/** @template T of ResultA */
interface I {
	/** @var class-string<T> */
	public const string ResultType = ResultA::class;
}

class ResultA {
	public function __construct(public string $value) {}
}

class ResultB extends ResultA {
	public function rot13(): string { return str_rot13($this->value); }
}

/** @template-implements I<ResultB> */
class In implements I {
	public const string ResultType = ResultB::class;
}

/**
 * @template T of ResultA
 * @param I<T> $in
 * @return T
 */
function run(I $in): ResultA {
	$value = 'abc';
	return new ($in::ResultType)($value);
}

function main(): void {
	$in = new In();
	$ret = run($in);
	print $ret->rot13();
}
