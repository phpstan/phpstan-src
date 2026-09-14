<?php // lint >= 8.5

namespace StaticMethodCallStatementResultDiscardedPhp85;

class ClassWithStaticSideEffects {
	#[\NoDiscard]
	public static function staticMethod(): array {
		echo __METHOD__ . "\n";
		return [2];
	}
}

class Foo
{

	public static function canDiscard(): array {
		return [];
	}

}

(void)ClassWithStaticSideEffects::staticMethod();
(void) Foo::canDiscard();

5 |> ClassWithStaticSideEffects::staticMethod(...);
5 |> Foo::canDiscard(...);
(void) 5 |> ClassWithStaticSideEffects::staticMethod(...);
(void) 5 |> Foo::canDiscard(...);

5 |> (fn ($x) => ClassWithStaticSideEffects::staticMethod($x));
5 |> (fn ($x) => Foo::canDiscard($x));
(void) 5 |> (fn ($x) => ClassWithStaticSideEffects::staticMethod($x));
(void) 5 |> (fn ($x) => Foo::canDiscard($x));
