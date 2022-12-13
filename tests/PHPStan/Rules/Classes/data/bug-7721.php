<?php // lint >= 8.1

namespace Bug7721;

final class A { }
final class B { }
final class C
{
	public function __construct(public readonly A|B $value) { }
}

$c = new C(value: new A());

echo match (true) {
	$c->value instanceof A => 'A',
	$c->value instanceof B => 'B'
};
