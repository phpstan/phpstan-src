<?php

namespace MethodCallStatementResultDiscarded;

class ClassWithStaticSideEffects {
	#[\NoDiscard]
	public static function staticMethod(): array {
		echo __METHOD__ . "\n";
		return [2];
	}

	#[\nOdISCArD]
	public static function differentCase(): array {
		echo __METHOD__ . "\n";
		return [2];
	}
}

ClassWithStaticSideEffects::staticMethod();

(void)ClassWithStaticSideEffects::staticMethod();

foreach (ClassWithStaticSideEffects::staticMethod() as $num) {
	var_dump($num);
}

ClassWithStaticSideEffects::differentCase();

ClassWithStaticSideEffects::staticMethod(...);
