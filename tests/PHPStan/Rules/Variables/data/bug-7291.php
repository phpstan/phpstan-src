<?php // lint >= 8.0

namespace Bug7291;

class Foo
{

	public ?Foo $foo = null;

	public function bar(): self
	{
		return $this;
	}

}

function bareProp(): void
{
	if (rand(0, 1)) {
		$a = rand(0, 1) ? new Foo() : null;
	}

	echo $a?->foo; // warn
}

function bareMethod(): void
{
	if (rand(0, 1)) {
		$b = rand(0, 1) ? new Foo() : null;
	}

	echo $b?->bar(); // warn
}

function bareChain(): void
{
	if (rand(0, 1)) {
		$c = rand(0, 1) ? new Foo() : null;
	}

	echo $c?->bar()?->foo; // warn
}

function notNullableStillWarns(): void
{
	if (rand(0, 1)) {
		$d = new Foo();
	}

	echo $d?->foo; // warn
}

function propCoalesce(): void
{
	if (rand(0, 1)) {
		$e = rand(0, 1) ? new Foo() : null;
	}

	echo $e?->foo ?? 0; // no warn, ?? handles it
}

function methodCoalesce(): void
{
	if (rand(0, 1)) {
		$f = rand(0, 1) ? new Foo() : null;
	}

	echo $f?->bar() ?? 0; // no warn, ?? handles it
}

function propIsset(): void
{
	if (rand(0, 1)) {
		$g = rand(0, 1) ? new Foo() : null;
	}

	var_dump(isset($g?->foo)); // no warn, isset handles it
}

function propEmpty(): void
{
	if (rand(0, 1)) {
		$h = rand(0, 1) ? new Foo() : null;
	}

	var_dump(empty($h?->foo)); // no warn, empty handles it
}

function methodEmpty(): void
{
	if (rand(0, 1)) {
		$i = rand(0, 1) ? new Foo() : null;
	}

	var_dump(empty($i?->bar())); // no warn, empty handles it
}
