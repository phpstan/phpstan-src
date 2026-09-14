<?php // lint >= 8.1

namespace FunctionCallStatementResultDiscarded;

#[\NoDiscard]
function withSideEffects(int $i): array {
	echo __FUNCTION__ . "\n";
	return [1];
}

withSideEffects(5);

foreach (withSideEffects(5) as $num) {
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
$firstClasCallableResult = $firstClassCallable(5);

$firstClassCallable(5);

$closureWithNoDiscard = #[\NoDiscard] function () { return 1; };
$a = $closureWithNoDiscard();

$closureWithNoDiscard();

$arrowWithNoDiscard = #[\NoDiscard] fn () => 1;
$b = $arrowWithNoDiscard();

$arrowWithNoDiscard();

withSideEffects(...);

function canDiscard(int $i): int
{
	return 1;
}

canDiscard(5);

$canDiscardCb = 'FunctionCallStatementResultDiscarded\\canDiscard';
$canDiscardCb();
