<?php // lint >= 8.0

namespace ClosureAttributeClassConstant;

#[\Attribute]
class Attr
{

	public function __construct(public string $value)
	{
	}

}

#[\Attribute]
class AttrWithoutConstructor
{

}

class Foo
{

	public function doFoo(): void
	{
		$f = #[Attr(Missing::FOO)] function () {};
		$g = #[Attr(Missing::FOO)] fn () => 1;
		$h = #[AttrWithoutConstructor(Missing::FOO)] static function () {};
		$i = #[Attr(self::BAR)] fn () => #[Attr(Missing::FOO)] fn () => 1;
	}

}
