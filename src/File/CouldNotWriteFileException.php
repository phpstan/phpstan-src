<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\AnalysedCodeException;
use function sprintf;

final class CouldNotWriteFileException extends AnalysedCodeException
{

	public function __construct(string $fileName, string $error)
	{
		parent::__construct(sprintf('Could not write file: %s (%s)', $fileName, $error));
	}

	public function getTip(): ?string
	{
		return null;
	}

}
