<?php

namespace Bug10159;

class Mixin {
	public function someMethod(): object
	{
		return (object) [];
	}
}

/**
 * @mixin Mixin
 */
class ParentClass
{}

/**
 * @method ChildClass someMethod()
 */
class ChildClass extends ParentClass
{
	public function methodFromChild(): void
	{}
}

function (): void {
	$childClass = new ChildClass();
	$childClass->someMethod()->methodFromChild();
};
