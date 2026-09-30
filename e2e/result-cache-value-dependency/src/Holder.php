<?php

namespace ResultCacheE2EValueDependency;

class Holder
{

	private $debug;

	public function __construct()
	{
		$this->debug = parameter('debug');
	}

	public function isDebug(): int
	{
		return $this->debug;
	}

}
