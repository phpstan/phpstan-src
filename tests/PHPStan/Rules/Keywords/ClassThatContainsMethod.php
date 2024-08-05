<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

class ClassThatContainsMethod
{

	public function getFileThatDoesNotExist(): string
	{
		return 'a-file-that-does-not-exist.txt';
	}

}
