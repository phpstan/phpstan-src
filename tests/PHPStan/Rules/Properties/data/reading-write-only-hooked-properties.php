<?php // lint >= 8.4

namespace ReadingWriteOnlyHookedProperties;

interface Foo
{

	public int $i {
		// virtual, not readable
		set;
	}

}

function (Foo $f): void {
	echo $f->i;
};

class Bar
{

	public int $other;

	public int $i {
		// virtual, not readable
		set {
			$this->other = 1;
		}
	}

}

function (Bar $b): void {
	echo $b->i;
};

class Baz
{

	public int $i {
		// backed, readable
		set {
			$this->i = 1;
		}
	}

}

function (Baz $b): void {
	$b->i = 1;
};
