<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

/** @api */
interface NamespaceAnswerer
{

	public function getNamespace(): ?string;

}
