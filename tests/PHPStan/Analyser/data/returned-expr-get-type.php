<?php // lint >= 8.0

declare(strict_types = 1);

namespace ReturnedExprGetType;

/** @template T */
final class Bag {
	/** @param list<T> $items */
	public function __construct(private array $items) {}
	/** @return list<T> */
	public function all(): array { return $this->items; }
}

/**
 * @template TReturnType
 * @param callable(): TReturnType $callback
 * @return TReturnType
 */
function once4(callable $callback) { return $callback(); }

final class Box4 {
	/** @return Bag<string> */
	public function items(): Bag {
		return once4(function (): Bag { return new Bag(['a']); });
	}
}
