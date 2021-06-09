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
		return implode('/', $this->getFilenameParts($filename));
	}

	/**
	 * @param string $filename
	 * @return string[]
	 */
	public function getFilenameParts(string $filename): array
	{
		$schemePosition = strpos($filename, '://');
		if ($schemePosition !== false) {
			$filename = substr($filename, $schemePosition + 3);
		}
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
			return [$filename];
		}

		$dotsCount = $parentPartsCount - $i;

		return array_merge(array_fill(0, $dotsCount, '..'), array_slice($filenameParts, $i));
	}

}
