<?php declare(strict_types = 1);

namespace PHPStan\File;

class NullRelativePathHelper implements RelativePathHelper
{

	public function getRelativePath(string $filename): string
	{
		return $filename;
	}

}
