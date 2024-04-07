<?php

namespace PureCallableMethodAccepts;

class Foo
{

	public function acceptsCallable(callable $cb): void
	{

	}

	/**
	 * @param pure-callable $cb
	 */
	public function acceptsPureCallable(callable $cb): void
	{

	}

	public function acceptsInt(int $i): void
	{

	}

	/**
	 * @param pure-callable $pureCb
	 */
	public function doFoo(callable $cb, callable $pureCb): void
	{
		$this->acceptsCallable($cb);
		$this->acceptsCallable($pureCb);
		$this->acceptsPureCallable($cb);
		$this->acceptsPureCallable($pureCb);
		$this->acceptsInt($cb);
		$this->acceptsInt($pureCb);

		$this->acceptsPureCallable(function (): int {
			return 1;
		});
		$this->acceptsPureCallable(function (): int {
			sleep(1);

			return 1;
		});
	}

	/**
	 * @param pure-Closure $cb
	 */
	public function acceptsPureClosure(\Closure $cb): void
	{

	}

	public function doFoo2(): void
	{
		$this->acceptsPureClosure(function (): int {
			return 1;
		});
		$this->acceptsPureClosure(function (): int {
			sleep(1);

			return 1;
		});
	}

}
