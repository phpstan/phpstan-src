<?php

namespace ResultCacheE2EStubEdit;

class UseChild
{

	public function run(Child $child): int
	{
		return $child->get();
	}

}
