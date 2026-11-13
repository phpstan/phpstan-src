<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitAliasModifier;

class Foo
{

	use HelloTrait {
		hello as protected;
	}

}
