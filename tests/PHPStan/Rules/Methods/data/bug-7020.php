<?php declare(strict_types = 1);

namespace Bug7020;

class TemplatePaths
{
	/**
	 * @param array<int, string> ...$templatePaths
	 * @return array<int, string>
	 */
	public static function merge(array ...$templatePaths): array
	{
		$mergedTemplatePaths = array_replace(...$templatePaths);
		ksort($mergedTemplatePaths);

		return $mergedTemplatePaths;
	}
}
