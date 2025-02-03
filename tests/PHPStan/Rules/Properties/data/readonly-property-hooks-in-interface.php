<?php declare(strict_types=1);

namespace ReadonlyPropertyHooksInInterface;

interface HelloWorld
{
	public readonly string $firstName { get; set; }

	public readonly string $middleName { get; }

	public readonly string $lastName { set; }
}
