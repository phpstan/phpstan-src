<?php declare(strict_types = 1);

namespace PHPStan\Type;

final class DirectTypeAliasResolverProvider implements TypeAliasResolverProvider
{

	public function __construct(private TypeAliasResolver $typeAliasResolver)
	{
	}

	public function getTypeAliasResolver(): TypeAliasResolver
	{
		return $this->typeAliasResolver;
	}

}
