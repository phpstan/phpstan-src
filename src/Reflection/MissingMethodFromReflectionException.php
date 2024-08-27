<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Exception;
use function sprintf;

final class MissingMethodFromReflectionException extends Exception
{

	public function __construct(
		string $className,
		string $methodName,
	)
	{
		parent::__construct(
			sprintf(
				'Method %s() was not found in reflection of class %s.',
				$methodName,
				$className,
			),
		);
	}

}
