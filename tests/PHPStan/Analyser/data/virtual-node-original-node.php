<?php declare(strict_types = 1);

namespace VirtualNodeOriginalNodeCallback;

final class Foo
{
	public static function doFoo(): void
	{
	}

	public function doFoo(): void
	{
		$cb = self::doFoo(...);
		$cb();
	}
}
