<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitAliasMethod;

class Foo
{

	use NumberTrait {
		two as number;
	}

}
