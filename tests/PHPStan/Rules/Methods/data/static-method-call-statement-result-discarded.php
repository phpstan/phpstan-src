<?php

namespace MethodCallStatementResultDiscarded;

class ClassWithStaticSideEffects {
	#[\NoDiscard]
	public static function staticMethod(): int {
		echo __METHOD__ . "\n";
		return 2;
	}
}

ClassWithStaticSideEffects::staticMethod();

(void)ClassWithStaticSideEffects::staticMethod();
