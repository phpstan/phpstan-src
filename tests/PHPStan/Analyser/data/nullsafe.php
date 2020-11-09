<?php

namespace Nullsafe;

use function PHPStan\Analyser\assertType;

class Foo
{

	private ?self $nullableSelf;

	private self $self;

	public function doFoo(?\Exception $e)
	{
		assertType('string|null', $e?->getMessage());
		assertType('Exception|null', $e);

		assertType('Throwable|null', $e?->getPrevious());
		assertType('string|null', $e?->getPrevious()?->getMessage());

		$e?->getMessage(assertType('Exception', $e));
	}

	public function doBar(?\ReflectionClass $r)
	{
		assertType('class-string<object>', $r->name);
		assertType('class-string<object>|null', $r?->name);

		assertType('Nullsafe\Foo|null', $this->nullableSelf?->self);
	}

}
