<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Exception;
use function sprintf;

final class MissingConstantFromReflectionException extends Exception
{

	public function __construct(
		string $className,
		string $constantName,
	)
	{
		parent::__construct(
			sprintf(
				'Constant %s was not found in reflection of class %s.',
				$constantName,
				$className,
			),
		);
	}

}
