<?php

namespace AccessStaticTraitMemberPhpVersion;

trait FooTrait
{

	public static function create(): void
	{
	}

}

function doFoo(): void
{
	if (PHP_VERSION_ID < 80100) {
		FooTrait::create();
	}

	if (PHP_VERSION_ID >= 80100) {
		FooTrait::create();
	}
}
