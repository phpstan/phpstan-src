<?php

namespace ResultCacheE2ERuleEdit;

function a(): void
{
	foo(); // @phpstan-ignore r.foo
	foo();
	undefinedOne(); // @phpstan-ignore function.notFound
	strlen('x'); // @phpstan-ignore r.foo
	elsewhere();
}
