<?php // lint >= 8.1

namespace FinalConstantPhpVersions;

if (PHP_VERSION_ID >= 80100) {
	class SupportedInBranch
	{

		final public const FOO = 1;

	}
}

if (PHP_VERSION_ID < 80100) {
	class UnsupportedInBranch
	{

		final public const FOO = 1;

	}
}

class AlwaysUnsupported
{

	final public const FOO = 1;

}
