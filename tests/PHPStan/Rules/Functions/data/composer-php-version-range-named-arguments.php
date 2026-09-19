<?php // lint >= 8.0

namespace ComposerPhpVersionRangeNamedArguments;

function doFoo(int $i, int $j): void
{

}

function doBar(): void
{
	doFoo(i: 1, j: 2);
}
