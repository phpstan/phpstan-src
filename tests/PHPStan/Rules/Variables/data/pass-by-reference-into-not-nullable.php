<?php // lint >= 8.0

namespace PassByReferenceIntoNotNullable;

class Foo
{

	public function doFooNoType(&$test)
	{

	}

	public function doFooMixedType(mixed &$test)
	{

	}

	public function doFooIntType(int &$test)
	{

	}

	public function doFooNullableType(?int &$test)
	{

	}

	public function test()
	{
		$this->doFooNoType($one);
		$this->doFooMixedType($two);
		$this->doFooIntType($three);
		$this->doFooNullableType($four);
	}

}
