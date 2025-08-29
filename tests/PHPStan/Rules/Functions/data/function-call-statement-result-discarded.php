<?php

namespace FunctionCallStatementResultDiscarded;

#[\NoDiscard]
function withSideEffects(): int {
	echo __FUNCTION__ . "\n";
	return 1;
}

withSideEffects();

(void)withSideEffects();
