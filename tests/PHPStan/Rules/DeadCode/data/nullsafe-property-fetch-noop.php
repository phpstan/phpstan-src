<?php // lint >= 8.0

namespace NullsafePropertyFetchNoop;

class Foo
{

	public function doFoo(?\ReflectionClass $ref): void
	{
		$ref?->name;
	}

}
