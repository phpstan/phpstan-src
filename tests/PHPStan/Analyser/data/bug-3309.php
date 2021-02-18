<?php

namespace Bug3309;

class Player
{
	const BAR = PHP_INT_MAX;

	/** @var int */
	private $experience;

	public function __construct()
	{
		if ($this->experience > self::BAR) {
			$this->experience = self::BAR;
		}
	}
}
