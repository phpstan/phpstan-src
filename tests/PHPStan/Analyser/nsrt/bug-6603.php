<?php declare(strict_types = 1);

namespace Bug6603;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	/** @var mixed[]|null */
	private ?array $member = null;

	/**
	 * @return mixed[]
	 */
	public function getIT(): array
	{
		if (null === $this->member) {
			$this->member = [];
			$this->doSomething();
		}

		assertType('array<mixed>', $this->member);

		return $this->member;
	}

	private function doSomething(): void
	{
	}
}
