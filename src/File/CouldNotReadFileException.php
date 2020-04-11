<?php declare(strict_types = 1);

namespace PHPStan\File;

class CouldNotReadFileException extends \PHPStan\AnalysedCodeException
{

	public function __construct(string $fileName)
	{
		parent::__construct(sprintf('Could not read file: %s', $fileName));
	}

	public function getTip(): ?string
	{
		return null;
	}

}
