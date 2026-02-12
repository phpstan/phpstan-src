<?php // lint >= 8.0

namespace Bug14097;

class Manipulations
{
	public function __construct(
		public int|null $width = null,
		public string|null $bgColor = null,
	) {
	}
}

class PathUtility
{
	public static function parseManipulations(string $path): Manipulations
	{
		$manipulations = [];
		// Width
		if (preg_match('/_w(?<w>\d+)/', $path, $matches)) {
			$manipulations['width'] = (int)$matches['w'];
			$path = str_replace($matches[0], '', $path);
		}

		// Background color (when padding images)
		if (preg_match('/_rgb(?<rgb>(?:[0-9a-fA-F]{3}){1,2})/', $path, $matches)) {
			$manipulations['bgColor'] = $matches['rgb'];
			$path = str_replace($matches[0], '', $path);
		}

		if (!empty($manipulations)) {
			return new Manipulations(...$manipulations);
		}

		return new Manipulations(...[]);
	}
}
