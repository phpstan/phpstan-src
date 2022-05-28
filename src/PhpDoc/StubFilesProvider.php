<?php declare(strict_types = 1);

namespace PHPStan\PhpDoc;

interface StubFilesProvider
{

	/** @return string[] */
	public function getStubFiles(): array;

	/** @return string[] */
	public function getProjectStubFiles(): array;

}
