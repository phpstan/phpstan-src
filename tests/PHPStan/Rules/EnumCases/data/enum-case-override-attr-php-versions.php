<?php // lint >= 8.6

namespace EnumCaseOverrideAttrPhpVersions;

use Override;

interface Base
{

	public const FOO = 1;

}

if (PHP_VERSION_ID >= 80600) {
	enum SupportedInBranch implements Base
	{

		#[Override]
		case FOO;

	}
}

enum AlwaysUnsupported implements Base
{

	#[Override]
	case FOO;

}
