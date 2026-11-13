<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitPrecedence;

class Foo
{

	use IntTrait, StringTrait {
		IntTrait::value insteadof StringTrait;
	}

}
