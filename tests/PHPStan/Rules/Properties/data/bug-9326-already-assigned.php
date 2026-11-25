<?php // lint >= 8.1

namespace Bug9326AlreadyAssigned;

final class CalledTwice
{
	private readonly int $x;

	public function __construct()
	{
		$this->init();
		$this->init();
	}

	private function init(): void
	{
		$this->x = 2;
	}
}

final class CalledInBothBranches
{
	private readonly int $x;

	public function __construct(bool $b)
	{
		if ($b) {
			$this->init();
		} else {
			$this->init();
		}
	}

	private function init(): void
	{
		$this->x = 2;
	}
}

final class Promoted
{
	public function __construct(private readonly int $x)
	{
		$this->init();
	}

	private function init(): void
	{
		$this->x = 1;
	}
}
