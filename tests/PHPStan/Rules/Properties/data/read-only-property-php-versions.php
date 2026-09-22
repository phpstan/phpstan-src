<?php // lint >= 8.1

namespace ReadOnlyPropertyPhpVersions;

if (PHP_VERSION_ID >= 80100) {
	class SupportedInBranch
	{

		public readonly int $foo;

	}
}

if (PHP_VERSION_ID < 80100) {
	class UnsupportedInBranch
	{

		public readonly int $foo;

	}
}

class AlwaysUnsupported
{

	public readonly int $foo;

}
