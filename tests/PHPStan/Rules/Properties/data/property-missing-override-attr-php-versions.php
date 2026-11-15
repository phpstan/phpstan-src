<?php

namespace PropertyMissingOverrideAttrPhpVersions;

class Foo
{

	public int $foo = 1;

}

if (PHP_VERSION_ID >= 80500) {
	class SupportedInBranch extends Foo
	{

		public int $foo = 1;

	}
}

class DependsOnPhpVersion extends Foo
{

	public int $foo = 1;

}
