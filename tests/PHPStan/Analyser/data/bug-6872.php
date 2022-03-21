<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug6872;

/**
 * @template TState of object
 */
abstract class Bug6872 {
	public function __construct() {
		// empty
	}

	/**
	 * @param TState $state
	 */
	protected function saveState(object $state): void {
		$this->set($state);
	}

	/**
	 * @param object|array<mixed>|string|float|int|bool|null $value
	 */
	public function set(object|array|string|float|int|bool|null $value): mixed {
		return $value;
	}
}
