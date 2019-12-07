<?php declare(strict_types = 1);

namespace TraitErrors;

class ClassImplementingTraitWithAbstractMethod
{

	use TraitWithAbstractMethod;

	public function getTitle(): string
	{
		return 'foo';
	}

}
