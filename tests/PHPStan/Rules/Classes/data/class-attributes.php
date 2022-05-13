<?php

namespace ClassAttributes;

class Foo
{

}

#[\Attribute]
class Bar
{

}

#[\Attribute(\Attribute::TARGET_PROPERTY)]
class Baz
{

}

#[Nonexistent]
class Consecteur
{

}

#[Foo]
class Lorem
{

}

#[baR]
class Sit
{

}

#[Bar]
class Ipsum
{

}

#[Baz]
class Dolor
{

}

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class Repeatable
{

}

#[Repeatable, Repeatable, Bar]
#[Bar]
class Amet
{

}

#[self(1)]
class Blabla
{

}

#[\Attribute]
abstract class AbstractAttribute
{

}

#[AbstractAttribute]
class Blablabla
{

}

#[Bar(1)]
class Bleble
{

}

#[\Attribute]
class NonPublicConstructor
{

	protected function __construct()
	{

	}

}

#[NonPublicConstructor]
class Blebleble
{

}

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS)]
class AttributeWithConstructor
{

	public function __construct(int $i, string $s)
	{

	}

}

#[AttributeWithConstructor(1, 'foo')]
#[AttributeWithConstructor]
#[AttributeWithConstructor(1)]
#[AttributeWithConstructor(i: 1, s: 'foo', r: 'bar')]
class Blebleh
{

}

#[\Attribute]
interface InterfaceAsAttribute
{

}

#[InterfaceAsAttribute]
class ClassWithInterfaceAttribute
{}

#[\Attribute]
trait TraitAsAttribute
{

}

#[TraitAsAttribute]
class ClassWithTraitAttribute
{}

#[\Attribute(flags: \Attribute::TARGET_CLASS)]
class FlagsAttributeWithClassTarget
{

}

#[\Attribute(flags: \Attribute::TARGET_PROPERTY)]
class FlagsAttributeWithPropertyTarget
{

}

#[FlagsAttributeWithClassTarget]
class TestFlagsAttribute
{

}

#[FlagsAttributeWithPropertyTarget]
class TestWrongFlagsAttribute
{

}
