<?php declare(strict_types = 1);

namespace PHPStan\File;

class SimpleRelativePathHelper implements RelativePathHelper
{

	private string $currentWorkingDirectory;

	public function __construct(string $currentWorkingDirectory)
	{
		$this->currentWorkingDirectory = $currentWorkingDirectory;
	}

	public function getRelativePath(string $filename): string
	{
		if ($this->currentWorkingDirectory !== '' && strpos($filename, $this->currentWorkingDirectory) === 0) {
			return str_replace('\\', '/', substr($filename, strlen($this->currentWorkingDirectory) + 1));
		}

		return str_replace('\\', '/', $filename);
	}

}
