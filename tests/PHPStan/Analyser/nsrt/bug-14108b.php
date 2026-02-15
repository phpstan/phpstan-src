<?php // lint >= 8.0

namespace Bug14108b;

use function PHPStan\Testing\assertType;

class Foo
{
	public function __construct(private ?string $param)
	{
	}

	public function getParam(): ?string
	{
		return $this->param;
	}

	/**
	 * @phpstan-assert !null $this->getParam()
	 */
	public function narrowGetParam(): void
	{
	}
}

class Bar
{
	public function __construct(private ?int $param)
	{
	}

	public function getParam(): ?int
	{
		return $this->param;
	}

	/**
	 * @phpstan-assert int $this->getParam()
	 */
	public function narrowGetParam(): void
	{
	}
}

function test(Foo|Bar $fooOrBar): void
{
	assertType('int|string|null', $fooOrBar->getParam());

	$fooOrBar->narrowGetParam();

	assertType('int|string|null', $fooOrBar->getParam()); // could be 'int|string'
}
