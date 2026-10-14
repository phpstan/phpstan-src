<?php // lint >= 8.1

namespace EnumDebugInfoPhpVersions;

if (PHP_VERSION_ID >= 80600) {
	enum SupportedInBranch
	{
		public function __debugInfo(): array
		{
			return [];
		}
	}
}

if (PHP_VERSION_ID < 80600) {
	enum UnsupportedInBranch
	{
		public function __debugInfo(): array
		{
			return [];
		}
	}
}

enum DependsOnPhpVersion
{
	public function __debugInfo(): array
	{
		return [];
	}
}
