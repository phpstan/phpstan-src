<?php

namespace MethodCallStatementResultDiscarded;

class ClassWithInstanceSideEffects {
	#[\NoDiscard]
	public function instanceMethod(): array {
		echo __METHOD__ . "\n";
		return [2];
	}

	#[\nOdISCArD]
	public function differentCase(): array {
		echo __METHOD__ . "\n";
		return [2];
	}
}

$o = new ClassWithInstanceSideEffects();
$o->instanceMethod();
$o?->instanceMethod();

(void)$o->instanceMethod();
(void)$o?->instanceMethod();

foreach ($o->instanceMethod() as $num) {
	var_dump($num);
}

$o->differentCase();

$o->instanceMethod(...);

class Foo
{

	public function canDiscard(): array {
		return [];
	}

}

$foo = new Foo();
$foo->canDiscard();
(void) $foo->canDiscard();

5 |> $o->instanceMethod(...);
5 |> $foo->canDiscard(...);
(void) 5 |> $o->instanceMethod(...);
(void) 5 |> $foo->canDiscard(...);

5 |> (fn ($x) => $o->instanceMethod($x));
5 |> (fn ($x) => $foo->canDiscard($x));
(void) 5 |> (fn ($x) => $o->instanceMethod($x));
(void) 5 |> (fn ($x) => $foo->canDiscard($x));
