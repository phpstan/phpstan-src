<?php

declare(strict_types=1);

namespace Bug7561;

trait AaTrait
{
	/** @return array<int, int> */
	public function beforeAnonClass(): array
	{
		return [];
	}

	public function a(): void
	{
		$x = new class() {};
	}

	/** @return array<int, int> */
	public function afterAnonClass(): array
	{
		return [];
	}
}
