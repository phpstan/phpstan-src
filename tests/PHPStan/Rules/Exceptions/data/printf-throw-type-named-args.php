<?php // lint >= 8.0

namespace PrintfThrowTypeNamedArgs;

class Foo
{

	public function doFoo(string $name): void
	{
		try {
			$a = sprintf(format: '%s', values: $name);
		} catch (\ValueError $e) {

		} catch (\ArgumentCountError $e) {

		}
	}

}
