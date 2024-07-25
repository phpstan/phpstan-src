<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\ShouldNotHappenException;
use function array_fill;
use function array_merge;
use function array_slice;
use function count;
use function explode;
use function implode;
use function str_replace;
use function strpos;
use function substr;
use function trim;

final class ParentDirectoryRelativePathHelper implements RelativePathHelper
{

	public function __construct(private string $parentDirectory)
	{
	}

	public function getRelativePath(string $filename): string
	{
		return implode('/', $this->getFilenameParts($filename));
	}

	/**
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

		if ($dotsCount < 0) {
			throw new ShouldNotHappenException();
		}

		return array_merge(array_fill(0, $dotsCount, '..'), array_slice($filenameParts, $i));
	}

}
