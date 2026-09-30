<?php

namespace ResultCacheE2EFileDependency;

class Holder
{

	private $value;

	public function __construct()
	{
		$this->value = holderData();
	}

	public function get(): int
	{
		return $this->value;
	}

}
