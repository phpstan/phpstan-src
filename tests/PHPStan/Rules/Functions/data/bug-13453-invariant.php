<?php // lint >= 8.3

declare(strict_types = 1);

namespace Bug13453Invariant;

/** @template T */
class Container {
	/** @param T $value */
	public function __construct(public mixed $value) {}
}

class ResultA {
	public function __construct(public string $value) {}
}

class ResultB extends ResultA {}

/** @template T of ResultA */
interface I {
	/** @var class-string<T> */
	public const string ResultType = ResultA::class;
}

/** @template-implements I<ResultB> */
class In implements I {
	public const string ResultType = ResultB::class;
}

/**
 * @template T of ResultA
 * @param I<T> $in
 * @return Container<T>
 */
function run(I $in): Container {
	$value = 'abc';
	return new Container(new ($in::ResultType)($value));
}
