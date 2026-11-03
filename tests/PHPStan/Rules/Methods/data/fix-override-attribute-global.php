<?php // lint >= 8.3

class FixOverrideAttributeGlobalFoo
{

	public function doFoo(): void
	{

	}

}

class FixOverrideAttributeGlobalBar extends FixOverrideAttributeGlobalFoo
{

	public function doFoo(): void
	{

	}

}
