<?php

namespace Bug7265;

class HelloWorld
{
	/**
	 * @param literal-string $assetPath e.g. css/cmsfasttrack.css or js/fasttrack.js
	 * @return non-empty-string
	 */
	static public function assetId(string $assetPath)
	{
		if (strpos($assetPath, 'css/') !== 0 && strpos($assetPath, 'js/') !== 0 && strpos($assetPath, 'img/') !== 0) {
			throw new \RuntimeException('Invalid asset path "' . $assetPath . '"');
		}

		$mtime = filemtime($assetPath);

		if ($mtime === false) {
			throw new \RuntimeException('Unable to get filemtime for asset path "' . $assetPath . '"');
		}

		return (string)$mtime;
	}
}
