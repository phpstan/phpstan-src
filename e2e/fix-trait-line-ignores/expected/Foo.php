<?php declare(strict_types = 1);

namespace TraitLineIgnores;

class Foo implements Checked
{

	use UnusedParametersTrait;
	use PropertyTrait;
	use ConstantConditionTrait;
	use CheckedTrait;

}
