<?php declare(strict_types = 1);

namespace Bug5552;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function happyPath($mixed, object $o, string $s): void
	{
		if (get_parent_class($mixed) === A::class) {
			assertType('Bug5552\A|class-string<Bug5552\A>', $mixed);
		} else {
			assertType('mixed', $mixed);
		}
		assertType('mixed', $mixed);

		if (A::class === get_parent_class($mixed)) {
			assertType('Bug5552\A|class-string<Bug5552\A>', $mixed);
		} else {
			assertType('mixed', $mixed);
		}
		assertType('mixed', $mixed);

		if (get_parent_class($mixed) === 'Bug5552\A') {
			assertType('Bug5552\A|class-string<Bug5552\A>', $mixed);
		} else {
			assertType('mixed', $mixed);
		}
		assertType('mixed', $mixed);

		if ('Bug5552\A' === get_parent_class($mixed)) {
			assertType('Bug5552\A|class-string<Bug5552\A>', $mixed);
		} else {
			assertType('mixed', $mixed);
		}
		assertType('mixed', $mixed);

		if (get_parent_class($o) === A::class) {
			assertType('Bug5552\A', $o);
		}
		if (A::class === get_parent_class($o)) {
			assertType('Bug5552\A', $o);
		}

		if (get_parent_class($s) === A::class) {
			assertType('class-string<Bug5552\A>', $s);
		}
		if (A::class === get_parent_class($s)) {
			assertType('class-string<Bug5552\A>', $s);
		}
	}
}

class A {}
