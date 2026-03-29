<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug10690;

trait MyTrait
{
	private const AAA='aaa';
	private const BBB='bbb';
}

final class First
{
	use MyTrait;

	function a(): string {
		return self::AAA.self::BBB;
	}
}

final class SecondConsumer
{
	use MyTrait;

	function b(): string {
		return self::AAA;
	}
}
