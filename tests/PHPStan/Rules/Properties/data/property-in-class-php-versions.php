<?php // lint >= 8.5

namespace PropertyInClassPhpVersions;

if (PHP_VERSION_ID >= 80400) {
	class FinalSupportedInBranch
	{

		public final int $foo;

	}
}

if (PHP_VERSION_ID < 80400) {
	class FinalUnsupportedInBranch
	{

		public final int $foo;

	}
}

if (PHP_VERSION_ID >= 80500) {
	class AsymmetricStaticSupportedInBranch
	{

		public private(set) static int $foo;

	}
}

class AsymmetricStaticAlwaysUnsupported
{

	public private(set) static int $foo;

}

if (PHP_VERSION_ID >= 80400) {
	class HooksSupportedInBranch
	{

		public int $foo { get => 1; }

	}
}

if (PHP_VERSION_ID < 80400) {
	class HooksUnsupportedInBranch
	{

		public int $foo { get => 1; }

	}
}
