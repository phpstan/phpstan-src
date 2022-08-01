<?php declare(strict_types = 1);

final class A { }
final class B { }
final class C { }

final class Test
{
	public function __construct(public A|B|C $value) { }
}

$t = new Test(value: new A());

echo match ($t->value::class) {
	A::class => 'A',
	B::class => 'B',
	C::class => 'C'
};

echo match ($t->value::class) {
	A::class => 'A',
	B::class => 'B'
};
