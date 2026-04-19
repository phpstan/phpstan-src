<?php

declare(strict_types=1);

namespace Bug8985;

class HelloWorld
{
	/**
	 * @return array<string, callable>
	 */
	protected function getDefaultFunctions(): array
	{
		/** @var array<string, callable> $x */
		$x = (new Defaults())->getFunctions();
		return $x;
	}
}

class HelloWorld2
{
	/**
	 * @return array<string, callable>
	 */
	protected function getDefaultFunctions(): array
	{
		/** @var array<string, callable> */
		return (new Defaults())->getFunctions();
	}
}

class Defaults
{
	public function getFunctions(): mixed
	{
		return [];
	}
}
