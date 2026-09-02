<?php

namespace ResultCacheE2EPathRepository;

use Test\SymlinkedLib\SymlinkedDep;

class UsesSymlinked
{

	public function __construct(private SymlinkedDep $dep)
	{
	}

	public function doUsesSymlinked(): int
	{
		return $this->dep->doDep();
	}

}
