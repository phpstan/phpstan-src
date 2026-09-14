<?php // lint >= 8.5

namespace MethodCallStatementResultDiscardedPhp85;

class ClassWithInstanceSideEffects {
	#[\NoDiscard]
	public function instanceMethod(): array {
		echo __METHOD__ . "\n";
		return [2];
	}
}

class Foo
{

	public function canDiscard(): array {
		return [];
	}

}

$o = new ClassWithInstanceSideEffects();
$foo = new Foo();

(void)$o->instanceMethod();
(void)$o?->instanceMethod();
(void) $foo->canDiscard();

5 |> $o->instanceMethod(...);
5 |> $foo->canDiscard(...);
(void) 5 |> $o->instanceMethod(...);
(void) 5 |> $foo->canDiscard(...);

5 |> (fn ($x) => $o->instanceMethod($x));
5 |> (fn ($x) => $foo->canDiscard($x));
(void) 5 |> (fn ($x) => $o->instanceMethod($x));
(void) 5 |> (fn ($x) => $foo->canDiscard($x));
