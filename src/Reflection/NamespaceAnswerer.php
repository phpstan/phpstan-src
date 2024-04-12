<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/** @api */
interface NamespaceAnswerer
{

	/**
	 * @return non-empty-string|null
	 */
	public function getNamespace(): ?string;

}
