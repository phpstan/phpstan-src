<?php declare(strict_types = 1);

namespace DerivedScopeGetType;

function target(string $key): void
{
}

function doFoo(string $key): void
{
	target($key);
}
