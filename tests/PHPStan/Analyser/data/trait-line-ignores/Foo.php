<?php declare(strict_types = 1);

namespace TraitLineIgnores;

class Foo
{

	use UnusedParametersTrait;
	use PropertyTrait;
	use ConstantConditionTrait;

}
