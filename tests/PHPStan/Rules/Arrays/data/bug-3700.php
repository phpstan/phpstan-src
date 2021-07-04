<?php

namespace Bug3700;

class HelloWorld
{
	public function errorWithExtension(): void
	{
		$filePath = 'somefile.txt';
		$pathInfo = pathinfo($filePath);
		$extension = empty($pathInfo['extension']) ? '' : '.' . $pathInfo['extension'];
	}
}
