<?php declare(strict_types = 1);

namespace PHPStan\File;

class CouldNotWriteFileException extends \PHPStan\AnalysedCodeException
{

	public function __construct(string $fileName, string $error)
	{
		parent::__construct(sprintf('Could not write file: %s (%s)', $fileName, $error));
	}

}
