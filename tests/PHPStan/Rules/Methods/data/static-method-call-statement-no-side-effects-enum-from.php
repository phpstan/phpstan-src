<?php // lint >= 8.1

namespace StaticMethodCallStatementNoSideEffectsEnumFrom;

enum FooEnum: string
{

	case A = 'a';

}

class Foo
{

	/**
	 * @param list<string> $values
	 */
	public function doFoo(array $values): void
	{
		foreach ($values as $v) {
			FooEnum::from($v);
		}
	}

}
