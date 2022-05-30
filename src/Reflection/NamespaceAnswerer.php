<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

interface NamespaceAnswerer
{

	public function getNamespace(): ?string;

}
