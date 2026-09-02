<?php

namespace ResultCacheE2EPathRepository;

use Test\CopiedLib\CopiedDep;

class UsesCopied
{

	public function __construct(private CopiedDep $dep)
	{
	}

	public function doUsesCopied(): int
	{
		return $this->dep->doDep();
	}

}
