<?php // lint >= 8.6

namespace ClassConstantOverrideAttrPhpVersions;

use Override;

class Base
{

	public const FOO = 1;

}

if (PHP_VERSION_ID >= 80600) {
	class SupportedInBranch extends Base
	{

		#[Override]
		public const FOO = 1;

	}
}

class AlwaysUnsupported extends Base
{

	#[Override]
	public const FOO = 1;

}
