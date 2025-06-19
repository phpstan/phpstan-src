<?php

namespace ResultCacheE2ETraits;

class ClassMentioningClassUsingBarTrait
{

	public function doFoo(ClassUsingBarTrait $c): void
	{
		$c->doFooTrait();
	}

}
