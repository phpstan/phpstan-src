<?php // lint >= 8.4

namespace PropertiesInInterfacePhpVersions;

if (PHP_VERSION_ID >= 80400) {
	interface SupportedInBranch
	{

		public int $foo { get; }

	}
}

if (PHP_VERSION_ID < 80400) {
	interface UnsupportedInBranch
	{

		public int $foo { get; }

	}
}

interface AlwaysDeclared
{

	public int $foo { get; }

}
