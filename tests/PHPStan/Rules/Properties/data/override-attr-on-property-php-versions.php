<?php // lint >= 8.0

namespace OverrideAttrOnPropertyPhpVersions;

use Override;

if (PHP_VERSION_ID >= 80500) {
	class SupportedInBranch
	{

		#[Override]
		public int $foo;

	}
}

if (PHP_VERSION_ID < 80500) {
	class UnsupportedInBranch
	{

		#[Override]
		public int $foo;

	}
}

class AlwaysUnsupported
{

	#[Override]
	public int $foo;

}
