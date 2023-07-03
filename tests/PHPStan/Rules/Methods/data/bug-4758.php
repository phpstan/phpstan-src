<?php

namespace Bug4758;

trait TraitOne
{
	/**
	 * @return array<mixed>
	 */
	public function doStuff(): array
	{
		return [[]];
	}
}

trait TraitTwo
{
	use TraitOne {
		TraitOne::doStuff as doStuffFromTraitOne;
	}
}

class SomeController
{
	use TraitTwo;
}
