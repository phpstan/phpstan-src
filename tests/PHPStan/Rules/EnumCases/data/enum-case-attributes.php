<?php // lint >= 8.1

namespace EnumCaseAttributes;

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AttributeWithPropertyTarget
{

}

#[\Attribute(\Attribute::TARGET_CLASS_CONSTANT)]
class AttributeWithClassConstantTarget
{

}

#[\Attribute(\Attribute::TARGET_ALL)]
class AttributeWithTargetAll
{

}

enum Lorem
{

	#[AttributeWithPropertyTarget]
	case FOO;

}

enum Ipsum
{

	#[AttributeWithClassConstantTarget]
	case FOO;

}

enum Dolor
{

	#[AttributeWithTargetAll]
	case FOO;

}
