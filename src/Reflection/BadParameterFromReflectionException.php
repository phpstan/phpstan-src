<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

class BadParameterFromReflectionException extends \PHPStan\AnalysedCodeException
{

	public function __construct(
		string $className,
		string $methodName,
		?string $currentFilename
	)
	{
		parent::__construct(
			sprintf(
				'Bad parameter for method %s() found in reflection of class %s - probably either the wrong version of class is autoloaded or, in case of an extension, its signature is missing from the map.%s',
				$methodName,
				$className,
				$currentFilename !== null
					? sprintf(' The currently loaded version is at: %s', $currentFilename)
					: ''
			)
		);
	}

}
