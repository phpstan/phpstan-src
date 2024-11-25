<?php declare(strict_types=1);

namespace PropertyHooksVisibilityInInterface;

interface HelloWorld
{
	private string $firstName { get; }

	protected string $lastName { get; set; }

	public string $fullName { get; set; }
}
