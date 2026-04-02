<?php declare(strict_types = 1);

namespace Bug10422;

use stdClass;
use function PHPStan\Testing\assertType;

class Foo
{

	public function other(): bool
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
		$error = 'yes';
	}
	if ($error) {
		return;
	}
	assertType(Foo::class, $test);
}
