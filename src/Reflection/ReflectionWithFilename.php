<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/** @api */
interface ReflectionWithFilename
{

	public function getFileName(): ?string;

}
