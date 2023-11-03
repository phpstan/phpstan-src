<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use Exception;
use function error_get_last;
use function is_null;
use function sprintf;

final class DirectoryCreatorException extends Exception
{

	public function __construct(public readonly string $directory)
	{
		$error = error_get_last();

		parent::__construct(sprintf(
			'Failed to create directory "%s" (%s).',
			$directory,
			is_null($error) ? 'unknown cause' : $error['message'],
		));
	}

}
