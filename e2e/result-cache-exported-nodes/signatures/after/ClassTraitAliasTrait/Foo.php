<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitAliasTrait;

class Foo
{

	use IntTrait, StringTrait {
		IntTrait::value insteadof StringTrait;
		StringTrait::value as number;
	}

}
