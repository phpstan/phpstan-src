<?php declare(strict_types = 1);

namespace PHPStan\Internal;

use Exception;
use function sprintf;

final class DirectoryCreatorException extends Exception
{

	public function __construct(public readonly string $directory)
	{
		parent::__construct(sprintf('Failed to create directory "%s".', $directory));
	}

}
