<?php

namespace Bug7844b;

class Obj {}

class HelloWorld
{
	public Obj $p1;
	public Obj $p2;
	public Obj $p3;
	public Obj $p4;
	public Obj $p5;

	/** @param non-empty-list<Obj> $objs */
	public function __construct(array $objs)
	{
		\assert($objs !== []);
		$this->p1 = $objs[0];

		\assert($objs !== []);
		$this->p2 = $objs[array_key_last($objs)];

		\assert($objs !== []);
		$this->p3 = \array_pop($objs);

		\assert($objs !== []);
		$this->p4 = \array_shift($objs);

		\assert($objs !== []);
		$p = \array_shift($objs);
		$this->p5 = $p;

		\assert($objs !== []);
		$this->doSomething(\array_pop($objs));
	}

	private function doSomething(Obj $obj): void {}
}
