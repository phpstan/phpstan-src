<?php declare(strict_types = 1);

namespace PHPStan\Type;

interface TypeAliasResolverProvider
{

	public function getTypeAliasResolver(): TypeAliasResolver;

}
