<?php

namespace ClosureThisSubclass;

class BaseTestCase
{

	public function publicHelper(): string
	{
		return 'public';
	}

	protected function protectedHelper(): string
	{
		return 'protected';
	}

	private function privateHelper(): string
	{
		return 'private';
	}

}

function runInSubclass(\Closure $callback): void {}
function runInBase(\Closure $callback): void {}

runInSubclass(function (): void {
	$this->publicHelper();
	$this->protectedHelper();
	$this->privateHelper();
});

runInSubclass(fn (): array => [
	$this->publicHelper(),
	$this->protectedHelper(),
	$this->privateHelper(),
]);

runInBase(function (): void {
	$this->publicHelper();
	$this->protectedHelper();
	$this->privateHelper();
});

function outsideClosure(BaseTestCase $testCase): void
{
	$testCase->publicHelper();
	$testCase->protectedHelper();
	$testCase->privateHelper();
}
