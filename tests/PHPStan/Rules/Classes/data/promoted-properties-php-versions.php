<?php // lint >= 8.0

namespace PromotedPropertiesPhpVersions;

if (PHP_VERSION_ID >= 80000) {
	class SupportedInBranch
	{

		public function __construct(public int $foo)
		{
		}

	}
}

if (PHP_VERSION_ID < 80000) {
	class UnsupportedInBranch
	{

		public function __construct(public int $foo)
		{
		}

	}
}

class AlwaysUnsupported
{

	public function __construct(public int $foo)
	{
	}

}
