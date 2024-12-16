<?php // lint >= 8.4

namespace WritingToReadOnlyHookedProperties;

interface Foo
{

	public int $i {
		// virtual, not writable
		get;
	}

}

function (Foo $f): void {
	$f->i = 1;
};

class Bar
{

	public int $i {
		// virtual, not writable
		get {
			return 1;
		}
	}

}

function (Bar $b): void {
	$b->i = 1;
};

class Baz
{

	public int $i {
		// backed, writable
		get {
			return $this->i + 1;
		}
	}

}

function (Baz $b): void {
	$b->i = 1;
};
