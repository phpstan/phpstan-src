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

		$commonPrefix = $this->commonPrefix($filename, $this->currentWorkingDirectory . $this->directorySeparator);
		if ($commonPrefix) {

			$relativeFilename = str_replace($commonPrefix, '', $filename);
			$relativeRemaining = str_replace($commonPrefix, '', $this->currentWorkingDirectory);

			$remainingParts = explode($this->directorySeparator, $relativeRemaining);
			return str_repeat('..' . $this->directorySeparator, count($remainingParts)) . $relativeFilename;
		}

		return $filename;
	}

	private function commonPrefix(string $path1, string $path2): ?string
	{
		$commonPrefix = '';

		$i = 0;
		while (true) {
			if (isset($path1[$i]) && isset($path2[$i])) {
				if ($path1[$i] === $path2[$i]) {
					$commonPrefix .= $path1[$i];
				}
			}

			$i++;
		}

		if ($commonPrefix) {
			return $commonPrefix;
		}
		return null;
	}
}
