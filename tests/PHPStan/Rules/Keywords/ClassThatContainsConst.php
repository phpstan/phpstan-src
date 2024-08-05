<?php declare(strict_types = 1);

namespace PHPStan\Rules\Keywords;

class ClassThatContainsConst
{

	public const FILE_EXISTS = 'include-me-to-prove-you-work.txt';
	public const FILE_DOES_NOT_EXIST = 'a-file-that-does-not-exist.php';

}
