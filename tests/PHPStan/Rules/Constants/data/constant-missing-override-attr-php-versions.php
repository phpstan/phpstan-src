<?php

namespace ConstantMissingOverrideAttrPhpVersions;

class Foo
{

	public const FOO = 1;

}

if (PHP_VERSION_ID >= 80600) {
	class SupportedInBranch extends Foo
	{

		public const FOO = 1;

	}
}

class DependsOnPhpVersion extends Foo
{

	public const FOO = 1;

}
