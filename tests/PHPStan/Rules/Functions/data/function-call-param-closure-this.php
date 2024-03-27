<?php // lint >= 7.4

namespace FunctionCallParamClosureThis;

/**
 * @param-closure-this \stdClass $cb
 */
function acceptClosure(callable $cb): void
{

}

function (): void {
	acceptClosure(function () {

	});

	acceptClosure(static function () {

	});

	acceptClosure(fn () => 1);
	acceptClosure(static fn () => 1);
};
