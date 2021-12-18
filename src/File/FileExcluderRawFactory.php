<?php declare(strict_types = 1);

namespace PHPStan\File;

interface FileExcluderRawFactory
{

	/**
	 * @param string[] $analyseExcludes
	 */
	public function create(
		array $analyseExcludes,
	): FileExcluder;

}
