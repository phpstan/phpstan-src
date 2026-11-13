<?php declare(strict_types = 1);

namespace ResultCacheE2EExportedNodes\ClassTraitAliasMethod;

trait NumberTrait
{

	public function one(): int
	{
		return 1;
	}

	public function two(): string
	{
		return '2';
	}

}
