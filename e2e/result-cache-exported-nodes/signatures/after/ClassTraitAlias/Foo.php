<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitAlias;

class Foo
{

	use HelloTrait {
		hello as salute;
	}

}
