<?php // lint >= 8.1

namespace EnumAttributes;

#[\Attribute]
class AttributeWithoutSpecificTarget
{

}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class AttributeWithPropertyTarget
{

}

#[AttributeWithoutSpecificTarget]
enum EnumWithValidClassAttribute
{

}

#[AttributeWithPropertyTarget]
enum EnumWithInvalidClassAttribute
{

}
