<?php

namespace Bug8825;

class Endboss
{

	private bool $isBool;

	/**
	 * @param mixed[] $actionParameters
	 */
	public function __construct(
		array $actionParameters
	)
	{
		$this->isBool = $actionParameters['my_key'] ?? false;
	}

	public function use(): void
	{
		$this->isBool->someMethod();
	}
}
