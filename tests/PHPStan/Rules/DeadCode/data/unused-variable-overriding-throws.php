<?php // lint >= 8.0

namespace UnusedVariableOverridingThrows;

class Foo
{

	public function readInCatch(): void
	{
		$before = 'a';
		try {
			/** @throws \RuntimeException */
			$before = 'b';
		} catch (\RuntimeException) {
			echo $before;
		}
	}

	public function readInCatchAfterCall(): void
	{
		$before = 'a';
		try {
			/** @throws \RuntimeException */
			$before = $this->doFoo();
		} catch (\RuntimeException) {
			echo $before;
		}
	}

	public function noThrowsAnnotation(): void
	{
		$before = 'a';
		try {
			$before = 'b';
		} catch (\RuntimeException) {
			echo $before;
		}
		echo $before;
	}

	public function throwsVoid(): void
	{
		$before = 'a';
		try {
			/** @throws void */
			$before = 'b';
		} catch (\RuntimeException) {
			echo $before;
		}
		echo $before;
	}

	/**
	 * @throws \RuntimeException
	 */
	private function doFoo(): string
	{
		return 'b';
	}

}

class Bar
{

	/**
	 * @template T
	 * @param-immediately-invoked-callable $callback
	 * @param callable(): T $callback
	 * @return T
	 * @throws void
	 */
	public function transaction(callable $callback): mixed
	{
		return $callback();
	}

	public function readInCatchAfterCallbackThrows(): void
	{
		$before = 'a';
		try {
			$before = $this->transaction(static function (): string {
				/** @throws \RuntimeException */
				return 'b';
			});
		} catch (\RuntimeException) {
			echo $before;
		}
	}

	public function readInCatchAfterCallbackWithoutThrows(): void
	{
		$before = 'a';
		try {
			$before = $this->transaction(static function (): string {
				return 'b';
			});
		} catch (\RuntimeException) {
			echo $before;
		}
	}

}
