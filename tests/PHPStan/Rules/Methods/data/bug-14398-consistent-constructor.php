<?php // lint >= 8.0

namespace Bug14398ConsistentConstructor;

#[\Attribute]
class Marker
{

}

/** @phpstan-consistent-constructor */
class ParentClass
{

	public function __construct()
	{
	}

}

class ChildClass extends ParentClass
{

	#[Marker]
	protected function __construct()
	{
	}

}
