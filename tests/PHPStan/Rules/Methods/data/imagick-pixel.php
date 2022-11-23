<?php declare(strict_types = 1);

namespace ImagickPixelParam;

use ImagickPixel;

class HelloWorld
{
	public function sayHello(ImagickPixel $pixel): void
	{
		$pixel->getColor();
		$pixel->getColor(0);
		$pixel->getColor(1);
		$pixel->getColor(2);
	}
}
