<?php

namespace MemcachePoolGet;

class CacheShim extends \MemcachePool
{

	public function get($arg, &$flags = null, &$cas = null)
	{
		return false;
	}

}
