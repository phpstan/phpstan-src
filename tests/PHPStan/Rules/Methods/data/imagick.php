<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods\data;

use Imagick;

class ImagickText
{
	public function isLcmsInstalled(): bool
	{
		return str_contains(Imagick::getConfigureOptions()['DELEGATES'], 'lcms');
	}
}
