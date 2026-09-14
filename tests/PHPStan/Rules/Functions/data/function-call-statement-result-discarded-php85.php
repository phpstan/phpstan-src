<?php // lint >= 8.5

namespace FunctionCallStatementResultDiscardedPhp85;

#[\NoDiscard]
function withSideEffects(int $i): array {
	echo __FUNCTION__ . "\n";
	return [1];
}

function canDiscard(int $i): int
{
	return 1;
}

(void)withSideEffects(5);
(void) canDiscard(5);

$canDiscardCb = 'FunctionCallStatementResultDiscardedPhp85\\canDiscard';
(void) $canDiscardCb();

5 |> withSideEffects(...);
5 |> canDiscard(...);
(void) 5 |> withSideEffects(...);
(void) 5 |> canDiscard(...);

5 |> (fn ($x) => withSideEffects($x));
5 |> (fn ($x) => canDiscard($x));
(void) 5 |> (fn ($x) => withSideEffects($x));
(void) 5 |> (fn ($x) => canDiscard($x));
