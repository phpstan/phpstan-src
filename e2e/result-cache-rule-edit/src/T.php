<?php

namespace ResultCacheE2ERuleEdit;

trait T
{
	public function t(): void
	{
		foo();
		foo(); // @phpstan-ignore r.foo
	}
}
