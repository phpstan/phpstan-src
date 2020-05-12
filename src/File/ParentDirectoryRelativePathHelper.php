<?php declare(strict_types = 1);

namespace PHPStan\File;

use function array_slice;
use function str_replace;

class ParentDirectoryRelativePathHelper implements RelativePathHelper
{

	private string $parentDirectory;

	public function __construct(string $parentDirectory)
	{
		$this->parentDirectory = $parentDirectory;
	}

	public function getRelativePath(string $filename): string
	{
		$parentParts = explode('/', trim(str_replace('\\', '/', $this->parentDirectory), '/'));
		$parentPartsCount = count($parentParts);
		$filenameParts = explode('/', trim(str_replace('\\', '/', $filename), '/'));
		$filenamePartsCount = count($filenameParts);

		$i = 0;
		for (; $i < $filenamePartsCount; $i++) {
			if ($parentPartsCount < $i + 1) {
				break;
			}

			$parentPath = implode('/', array_slice($parentParts, 0, $i + 1));
			$filenamePath = implode('/', array_slice($filenameParts, 0, $i + 1));

			if ($parentPath !== $filenamePath) {
				break;
			}
		}

		if ($i === 0) {
			return $filename;
		}

		$dotsCount = $parentPartsCount - $i;

		return str_repeat('../', $dotsCount) . implode('/', array_slice($filenameParts, $i));
	}

}
