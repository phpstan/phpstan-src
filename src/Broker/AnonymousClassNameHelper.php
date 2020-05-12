<?php declare(strict_types = 1);

namespace PHPStan\Broker;

use PHPStan\File\FileHelper;
use PHPStan\File\RelativePathHelper;

class AnonymousClassNameHelper
{

	private FileHelper $fileHelper;

	private RelativePathHelper $relativePathHelper;

	public function __construct(
		FileHelper $fileHelper,
		RelativePathHelper $relativePathHelper
	)
	{
		$this->fileHelper = $fileHelper;
		$this->relativePathHelper = $relativePathHelper;
	}

	public function getAnonymousClassName(
		\PhpParser\Node\Stmt\Class_ $classNode,
		string $filename
	): string
	{
		if (isset($classNode->namespacedName)) {
			throw new \PHPStan\ShouldNotHappenException();
		}

		$filename = $this->relativePathHelper->getRelativePath(
			$this->fileHelper->normalizePath($filename, '/')
		);

		return sprintf(
			'AnonymousClass%s',
			md5(sprintf('%s:%s', $filename, $classNode->getLine()))
		);
	}

}
