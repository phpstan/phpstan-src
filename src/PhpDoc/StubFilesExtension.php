<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

/** @api */
interface StubFilesExtension
{

	public const EXTENSION_TAG = 'phpstan.stubFilesExtension';

	/** @return string[] */
	public function getFiles(): array;

}
