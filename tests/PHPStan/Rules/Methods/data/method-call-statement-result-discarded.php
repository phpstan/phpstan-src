<?php // lint >= 8.1

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
