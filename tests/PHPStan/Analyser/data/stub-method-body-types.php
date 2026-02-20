<?php

use function PHPStan\Testing\assertType;

class StubMethodBodyTypesClass
{
	/**
	 * Source declares @param string — stub overrides to @param callable-string
	 * @param string $callback
	 * @return string
	 */
	public function process($callback): string
	{
		// Stub should override the source file's @param type inside the method body
		assertType('callable-string', $callback);
		return $callback;
	}
}

class StubMethodBodyTypesExternalCaller
{
	public function test(StubMethodBodyTypesClass $obj): void
	{
		// Stub should also work for external callers (already worked before regression)
		assertType('non-empty-string', $obj->process('strlen'));
	}
}
