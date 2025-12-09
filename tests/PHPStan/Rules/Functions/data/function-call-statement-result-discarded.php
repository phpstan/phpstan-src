<?php // lint >= 8.1

namespace FunctionCallStatementResultDiscarded;

#[\NoDiscard]
function withSideEffects(int $i): array {
	echo __FUNCTION__ . "\n";
	return [1];
}

withSideEffects(5);

(void)withSideEffects(5);

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
(void) canDiscard(5);

$canDiscardCb = 'FunctionCallStatementResultDiscarded\\canDiscard';
$canDiscardCb();
(void) $canDiscardCb();

5 |> withSideEffects(...);
5 |> canDiscard(...);
(void) 5 |> withSideEffects(...);
(void) 5 |> canDiscard(...);

5 |> (fn ($x) => withSideEffects($x));
5 |> (fn ($x) => canDiscard($x));
(void) 5 |> (fn ($x) => withSideEffects($x));
(void) 5 |> (fn ($x) => canDiscard($x));
