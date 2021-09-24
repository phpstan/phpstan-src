<?php // lint >= 8.0

namespace ArrayDestructuringNullsafe;

class Foo
{

	public function doFooBar(?Bar $bar): void
	{
		[$a] = $bar?->getArray();
	}

}

class Bar
{

	public function getArray(): array
	{
		return [];
	}

}
