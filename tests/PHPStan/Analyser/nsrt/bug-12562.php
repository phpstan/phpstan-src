<?php declare(strict_types = 1); // lint >= 8.0

namespace Bug12562;

use function PHPStan\Testing\assertType;

class UserV1
{
	public function toV2(): UserV2
	{
		return new UserV2();
	}
}

class UserV2
{
	public function toV2(): self
	{
		return $this;
	}
}

class UserV2a extends UserV2
{
	/**
	 * @return $this
	 */
	public function toV2(): self
	{
		return $this;
	}
}

function doSomething(UserV1|UserV2 $user, UserV1|UserV2a $user2): void
{
	assertType('Bug12562\UserV2', $user->toV2());
	assertType('Bug12562\UserV2', $user2->toV2());
}
