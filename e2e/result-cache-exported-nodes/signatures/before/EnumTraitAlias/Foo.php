<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\EnumTraitAlias;

enum Foo
{

	use HelloTrait {
		hello as greet;
	}

	case A;

}
