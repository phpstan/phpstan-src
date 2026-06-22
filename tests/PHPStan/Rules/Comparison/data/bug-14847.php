<?php declare(strict_types = 1);

namespace Bug14847Comparison;

class Foo
{

	public ?string $n = null;

}

function f(Foo $obj): void
{
	if ($obj->n === null) {
		return;
	}

	if ($obj->{'n'} === null) {
		echo 'dead';
	}
}

function g(Foo $obj): void
{
	if ($obj->{'n'} === null) {
		return;
	}

	if ($obj->n === null) {
		echo 'dead';
	}
}
