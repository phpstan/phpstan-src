<?php // lint >= 8.0

namespace NativeUnionTypesPhpVersions;

if (PHP_VERSION_ID >= 80000) {
	function supportedInBranch(int|string $a): void
	{
	}
}

if (PHP_VERSION_ID < 80000) {
	function unsupportedInBranch(int|string $a): void
	{
	}
}

function alwaysUnsupported(int|string $a): void
{
}
