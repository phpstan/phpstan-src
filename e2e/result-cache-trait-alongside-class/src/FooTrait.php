<?php

namespace ResultCacheE2ETraitAlongsideClass;

trait FooTrait
{

	public function doFooTrait(): \stdClass
	{
		return new \stdClass();
	}

}

class FooTraitMarker
{

}
