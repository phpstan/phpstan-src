<?php // lint >= 8.3

namespace Bug13133;

if (PHP_VERSION_ID >= 80300) {
	class Foo
	{

		public const string BAR = 'bar';

	}
} else {
	class Foo
	{

		public const BAR = 'bar';

	}
}

class AlwaysTyped
{

	public const string BAR = 'bar';

}
