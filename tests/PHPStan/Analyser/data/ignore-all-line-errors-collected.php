<?php

namespace IgnoreAllLineErrorsCollected;

function doFoo(): void
{
	// @phpstan-ignore-next-line
	fail(); foo();
}
