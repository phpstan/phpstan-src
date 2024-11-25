<?php declare(strict_types=1);

namespace PropertyHooksInInterface;

interface HelloWorld
{
	public string $firstName { get; }

	public string $lastName { get; set; }
}
