<?php // lint >= 8.0

namespace UnusedMethodParametersOverridingThrows;

class Foo
{

	private function readOnlyInCatch(string $param): int
	{
		try {
			/** @throws \RuntimeException */
			$x = 1;
		} catch (\RuntimeException) {
			echo $param;
			$x = 2;
		}

		return $x;
	}

	public function doBar(): void
	{
		$this->readOnlyInCatch('x');
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

	private function readOnlyInCatchAfterCallbackThrows(string $param): string
	{
		try {
			return $this->transaction(static function (): string {
				/** @throws \RuntimeException */
				return 'b';
			});
		} catch (\RuntimeException) {
			return $param;
		}
	}

	public function doBar(): void
	{
		$this->readOnlyInCatchAfterCallbackThrows('x');
	}

}
