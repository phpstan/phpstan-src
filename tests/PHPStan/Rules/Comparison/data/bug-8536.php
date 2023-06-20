<?php

namespace Bug8536;

final class A {
	public function __construct(public readonly string $id) {}
}
final class B {
	public function __construct(public readonly string $name) {}
}

class Foo
{

	public function getValue(A|B $obj): string
	{
		return match(get_class($obj)) {
			A::class => $obj->id,
			B::class => $obj->name,
		};
	}

}
