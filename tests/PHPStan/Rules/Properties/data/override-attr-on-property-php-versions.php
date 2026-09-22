<?php // lint >= 8.5

namespace OverrideAttrOnPropertyPhpVersions;

use Override;

class Base
{

	public int $foo = 1;

}

if (PHP_VERSION_ID >= 80500) {
	class SupportedInBranch extends Base
	{

		#[Override]
		public int $foo = 1;

	}
}

class AlwaysUnsupported extends Base
{

	#[Override]
	public int $foo = 1;

}
