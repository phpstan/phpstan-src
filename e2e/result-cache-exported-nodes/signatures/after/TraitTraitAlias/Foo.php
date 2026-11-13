<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\TraitTraitAlias;

trait Foo
{

	use NumberTrait {
		two as number;
	}

}
