<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use Exception;
use function sprintf;

final class MissingPropertyFromReflectionException extends Exception
{

	public function __construct(
		string $className,
		string $propertyName,
	)
	{
		parent::__construct(
			sprintf(
				'Property $%s was not found in reflection of class %s.',
				$propertyName,
				$className,
			),
		);
	}

}
