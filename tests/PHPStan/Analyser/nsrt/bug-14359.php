<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14359;

use function PHPStan\Testing\assertType;

class Foo
{

	public function getId(): ?string
	{
		return 'foo';
	}

}

class FooService
{

	/**
	 * @throws \LogicException
	 */
	public function disable(Foo $foo): void
	{

	}

}

function (Foo $foo, FooService $service): void {
	if ($foo->getId() !== null) {
		try {
			assertType('string', $foo->getId());
			$service->disable($foo);
			assertType('string|null', $foo->getId());
		} catch (\LogicException) {
			assertType('string', $foo->getId());
		}
	}
};
