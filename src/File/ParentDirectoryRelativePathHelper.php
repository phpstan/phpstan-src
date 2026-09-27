<?php declare(strict_types = 1);

namespace PHPStan\File;

use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\NonAutowiredService;
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

#[NonAutowiredService(name: 'parentDirectoryRelativePathHelper')]
final class ParentDirectoryRelativePathHelper implements RelativePathHelper
{

	/** @var string[] */
	private array $parentParts;

	private int $parentPartsCount;

	public function __construct(
		#[AutowiredParameter(ref: '%currentWorkingDirectory%')]
		string $parentDirectory,
	)
	{
		$this->parentParts = explode('/', trim(str_replace('\\', '/', $parentDirectory), '/'));
		$this->parentPartsCount = count($this->parentParts);
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
		$filenameParts = explode('/', trim(str_replace('\\', '/', $filename), '/'));
		$filenamePartsCount = count($filenameParts);

		$i = 0;
		for (; $i < $filenamePartsCount; $i++) {
			if ($this->parentPartsCount < $i + 1) {
				break;
			}

			if ($this->parentParts[$i] !== $filenameParts[$i]) {
				break;
			}
		}

		if ($i === 0) {
			return [$filename];
		}

		$dotsCount = $this->parentPartsCount - $i;

		if ($dotsCount < 0) {
			throw new ShouldNotHappenException();
		}

		return array_merge(array_fill(0, $dotsCount, '..'), array_slice($filenameParts, $i));
	}

}
