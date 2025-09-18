<?php // lint >= 8.1

namespace FunctionCallStatementResultDiscarded;

#[\NoDiscard]
function withSideEffects(): array {
	echo __FUNCTION__ . "\n";
	return [1];
}

withSideEffects();

(void)withSideEffects();

foreach (withSideEffects() as $num) {
	var_dump($num);
}

#[\nOdISCArD]
function differentCase(): array {
	echo __FUNCTION__ . "\n";
	return [1];
}

differentCase();

$callable = 'FunctionCallStatementResultDiscarded\\withSideEffects';
$callableResult = $callable();

$callable();

$firstClassCallable = withSideEffects(...);
$firstClasCallableResult = $firstClassCallable();

$firstClassCallable();

$closureWithNoDiscard = #[\NoDiscard] function () { return 1; };
$a = $closureWithNoDiscard();

$closureWithNoDiscard();

$arrowWithNoDiscard = #[\NoDiscard] fn () => 1;
$b = $arrowWithNoDiscard();

$arrowWithNoDiscard();

withSideEffects(...);
