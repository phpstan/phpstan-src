<?php

namespace DeprecatedPropertyAttribute;

/**
 * @deprecated
 */
#[\Attribute]
final class DoSomethingTheOldWay
{
}


final class SomeDTO
{
	#[DoSomethingTheOldWay]
	public $property;

	#[DoSomethingTheOldWayWithDescription]
	public $property2;
}

/**
 * @deprecated Use something else please
 */
#[\Attribute]
final class DoSomethingTheOldWayWithDescription
{
}
