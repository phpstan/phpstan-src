<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/**
 * Provides the current namespace context.
 *
 * Used by the type resolver and PHPDoc parser to resolve relative class names
 * against the current namespace and use statements.
 *
 * @api
 */
interface NamespaceAnswerer
{

	/** @return non-empty-string|null */
	public function getNamespace(): ?string;

}
