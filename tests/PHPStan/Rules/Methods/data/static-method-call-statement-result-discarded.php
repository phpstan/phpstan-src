<?php

namespace StaticMethodCallStatementResultDiscarded;

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

class Foo
{

	public static function canDiscard(): array {
		return [];
	}

}

Foo::canDiscard();
(void) Foo::canDiscard();

5 |> ClassWithStaticSideEffects::staticMethod(...);
5 |> Foo::canDiscard(...);
(void) 5 |> ClassWithStaticSideEffects::staticMethod(...);
(void) 5 |> Foo::canDiscard(...);

5 |> (fn ($x) => ClassWithStaticSideEffects::staticMethod($x));
5 |> (fn ($x) => Foo::canDiscard($x));
(void) 5 |> (fn ($x) => ClassWithStaticSideEffects::staticMethod($x));
(void) 5 |> (fn ($x) => Foo::canDiscard($x));
