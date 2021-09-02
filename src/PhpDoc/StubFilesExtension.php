<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

interface StubFilesExtension
{

	public const EXTENSION_TAG = 'phpstan.stubFilesExtension';

	/** @return string[] */
	public function getFiles(): array;

}
