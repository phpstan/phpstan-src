<?php declare(strict_types = 1);

namespace UnusedMethodParametersByRefUse;

class Foo
{

	/**
	 * @param array<string, int> $expectedCalls
	 */
	private function capturedByReference(array $expectedCalls): void
	{
		$this->register(function (string $call) use (&$expectedCalls): int {
			$expectedCalls[$call]--;

			return $expectedCalls[$call];
		});
	}

	/**
	 * @param array<string, int> $expectedCalls
	 */
	private function capturedByReferenceAfterOverwrite(array $expectedCalls): void
	{
		$expectedCalls = [];
		$this->register(function (string $call) use (&$expectedCalls): int {
			$expectedCalls[$call]--;

			return $expectedCalls[$call];
		});
	}

	public function register(callable $callback): void
	{
	}

	public function doFoo(): void
	{
		$this->capturedByReference([]);
		$this->capturedByReferenceAfterOverwrite([]);
	}

}
