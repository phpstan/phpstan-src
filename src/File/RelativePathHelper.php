<?php declare(strict_types = 1);

namespace PHPStan\File;

/** @api */
interface RelativePathHelper
{

	public function getRelativePath(string $filename): string;

}
