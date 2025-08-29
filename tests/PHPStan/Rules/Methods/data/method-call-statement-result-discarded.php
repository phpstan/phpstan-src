<?php

namespace MethodCallStatementResultDiscarded;

class ClassWithInstanceSideEffects {
	#[\NoDiscard]
	public function instanceMethod(): int {
		echo __METHOD__ . "\n";
		return 2;
	}
}

$o = new ClassWithInstanceSideEffects();
$o->instanceMethod();
$o?->instanceMethod();

(void)$o->instanceMethod();
(void)$o?->instanceMethod();
