<?php

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
