<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;

interface TypeAliasResolver
{

	public function hasTypeAlias(string $aliasName, ?string $classNameScope): bool;

	public function resolveTypeAlias(string $aliasName, NameScope $nameScope): ?Type;

}
