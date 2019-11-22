<?php

namespace TooWideArrowFunctionReturnType;

class Foo
{

	public function doFoo(?string $nullableString)
	{
		fn (): \Generator => yield 1;

		fn (): ?string => null;

		fn (): ?string => 'foo';

		fn (): ?string => $nullableString;
	}

}
