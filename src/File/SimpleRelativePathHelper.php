<?php declare(strict_types = 1);

namespace PHPStan\File;

class SimpleRelativePathHelper implements RelativePathHelper
{

	/** @var string */
	private $directorySeparator;

	/** @var string */
	private $currentWorkingDirectory = '';

	public function __construct(string $currentWorkingDirectory, string $directorySeparator = DIRECTORY_SEPARATOR)
	{
		$this->directorySeparator = $directorySeparator;

		if ($currentWorkingDirectory !== $directorySeparator) {
			$currentWorkingDirectory = rtrim($currentWorkingDirectory, $directorySeparator);
		}
		$this->currentWorkingDirectory = $currentWorkingDirectory;
	}

	public function getRelativePath(string $filename): string
	{
		if ($this->currentWorkingDirectory === '') {
			return $filename;
		}

		if ($this->currentWorkingDirectory === $this->directorySeparator) {
			if (strpos($filename, $this->currentWorkingDirectory) === 0) {
				return substr($filename, strlen($this->currentWorkingDirectory));
			}

			return $filename;
		}

		if (strpos($filename, $this->currentWorkingDirectory . $this->directorySeparator) === 0) {
			return substr($filename, strlen($this->currentWorkingDirectory) + 1);
		}

		return $filename;
	}

}
