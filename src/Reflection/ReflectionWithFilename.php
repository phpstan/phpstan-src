<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/** @api */
interface ReflectionWithFilename
{

	/**
	 * @return string|false
	 */
	public function getFileName();

}
