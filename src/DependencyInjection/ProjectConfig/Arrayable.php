<?php declare(strict_types = 1);

namespace PHPStan\DependencyInjection\ProjectConfig;

interface Arrayable
{

	/** @return array<array-key, mixed> */
	public function toArray(): array;

}
