<?php declare(strict_types = 1);

namespace PHPStan\Rules\Comparison;

use PHPStan\Analyser\Error;
use PHPStan\Turbo\ShadowedByTurboExtension;

/**
 * The constant-condition collectors carry the reported Error in their collected value, so the
 * result cache cannot reach its paths on its own - see CollectorWithPaths.
 */
#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../../turbo-ext/src/CollectedConstantConditionError.cpp')]
final class CollectedConstantConditionError
{

	/**
	 * @param Error|array<mixed> $error
	 * @param callable(string): string $transformPath
	 * @return Error|array<mixed>
	 */
	public static function transformPaths($error, callable $transformPath)
	{
		if ($error instanceof Error) {
			return $error->transformPaths($transformPath);
		}

		// an Error that crossed a parallel worker boundary arrives as its JSON form
		return Error::decode($error)->transformPaths($transformPath)->jsonSerialize();
	}

}
