<?php // lint >= 8.5

namespace ErrorGetLastPhp85;

use function PHPStan\Testing\assertType;

function test(): void
{
	assertType("array{type: int, message: string, file: string, line: int, trace?: list<array{function: string, line?: int, file?: string, class?: class-string, type?: '->'|'::', args?: list<mixed>, object?: object}>}|null", error_get_last());
}
