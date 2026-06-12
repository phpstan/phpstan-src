<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ResultCache;

use RuntimeException;
use Throwable;
use function sprintf;

/**
 * Wraps an exception thrown from ResultCacheMetaExtension::getHash() so that it can be
 * caught and reported as an internal error instead of escaping as a fatal that a global
 * exception handler might swallow into a 0 exit code.
 */
final class ResultCacheMetaExtensionException extends RuntimeException
{

	public function __construct(
		private string $extensionClass,
		Throwable $previous,
	)
	{
		parent::__construct(
			sprintf('Result cache meta extension %s threw an exception: %s', $extensionClass, $previous->getMessage()),
			previous: $previous,
		);
	}

	public function getExtensionClass(): string
	{
		return $this->extensionClass;
	}

}
