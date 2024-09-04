<?php

namespace Bug8727;

abstract class Foo
{
	abstract public function hello(): void;

	protected function message(): string
	{
		if (property_exists($this, 'lala')) {
			return 'Lala!';
		}

		return 'Hello!';
	}
}

class Bar extends Foo {
	protected bool $lala = true;

	public function hello(): void
	{
		echo $this->message();
	}
}
