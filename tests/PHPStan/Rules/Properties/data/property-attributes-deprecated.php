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
	public readonly string $property;

	#[DoSomethingTheOldWayWithDescription]
	public readonly string $property2;
}

/**
 * @deprecated Use something else please
 */
#[\Attribute]
final class DoSomethingTheOldWayWithDescription
{
}
