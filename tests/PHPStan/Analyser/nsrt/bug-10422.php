<?php declare(strict_types = 1);

namespace Bug10422;

use stdClass;
use function PHPStan\Testing\assertType;

class Foo
{

	public function something(): bool
	{
		return true;
	}

	public function test(): void
	{
	}

}

function createOrNotObject(): ?Foo
{
	return new Foo();
}

function testSimple(): void
{
	$test = createOrNotObject();

	$error = '';

	if (!$test) {
		$error = 'missing test';
	} else if ($test->something()) {
		$error = 'another';
	}
	if ($error) {
		die('Done');
	}
	assertType(Foo::class, $test);
}
