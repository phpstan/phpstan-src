<?php // lint >= 8.2

namespace ConstantsInTraitsPhpVersions;

trait SupportedTrait
{
	const FOO = 'foo';
}

trait UnsupportedTrait
{
	const FOO = 'foo';
}

trait AlwaysUsedTrait
{
	const FOO = 'foo';
}

if (PHP_VERSION_ID >= 80200) {
	class SupportedConsumer
	{
		use SupportedTrait;
	}
}

if (PHP_VERSION_ID < 80200) {
	class UnsupportedConsumer
	{
		use UnsupportedTrait;
	}
}

class AlwaysConsumer
{
	use AlwaysUsedTrait;
}
