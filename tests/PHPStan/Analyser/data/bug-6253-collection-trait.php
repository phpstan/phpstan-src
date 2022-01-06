<?php

namespace Bug6253;

trait CollectionTrait
{
	public function doFoo(): void
	{
		$c = new class () {
			use AppScopeTrait;
		};
	}
}
