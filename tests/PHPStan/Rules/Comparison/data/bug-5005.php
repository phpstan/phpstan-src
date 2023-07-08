<?php

namespace Bug5005;

class HelloWorld
{
	const IMAGE_TARGET_RATIO = 4 / 3;

	private function forceImageRatio(): void
	{
		$requiredRatio = self::IMAGE_TARGET_RATIO;

		$source = imagecreatefrompng('myimage.png');
		if (false === $source) {
			return;
		}

		$srcWidth = imagesx($source);
		$srcHeight = imagesy($source);
		$srcRatio = $srcWidth / $srcHeight;

		if ($srcRatio > $requiredRatio) {

		} elseif ($srcRatio < $requiredRatio) {

		}
	}
}
