<?php declare(strict_types = 1);

namespace PHPStan\Testing;

/** @internal */
interface ComposerAutoloaderProjectPathsProvider
{

	/** @return string[] */
	public static function getComposerAutoloaderProjectPaths(): array;

}
