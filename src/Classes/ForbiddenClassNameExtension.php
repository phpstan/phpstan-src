<?php declare(strict_types = 1);


namespace PHPStan\Classes;


interface ForbiddenClassNameExtension
{
	public const EXTENSION_TAG = 'phpstan.forbiddenClassNamesExtension';

	/** @return array<string, string> */
	public function getClassPrefixes(): array;
}
