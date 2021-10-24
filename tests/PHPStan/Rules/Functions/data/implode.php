<?php declare(strict_types = 1);

namespace ImplodeFunction;

class Foo
{
	public function invalidArgs(): void
	{
		implode('', ['12', '123', ['1234', '12345']]);

		implode([['1234', '12345']]);
		implode([[1234, 12345]]);
		implode([[true, 12345]]);

		implode('', [['1234', '12345']]);
		join('', [['1234', '12345']]);
	}

	public function valid() {
		implode(['12', '345']);

		implode('', ['12', '345']);
		join('', ['12', '345']);
	}

}
