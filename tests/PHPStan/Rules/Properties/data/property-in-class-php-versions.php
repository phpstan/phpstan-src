<?php // lint >= 8.4

namespace PropertyInClassPhpVersions;

if (PHP_VERSION_ID >= 80400) {
	class SupportedInBranch
	{

		final public int $foo = 1;

		public int $bar { get => 1; }

	}
}

if (PHP_VERSION_ID < 80400) {
	class UnsupportedInBranch
	{

		final public int $foo = 1;

	}
}

class AlwaysUnsupported
{

	final public int $foo = 1;

}
