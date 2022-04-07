<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

class DirectConstantResolverProvider implements ConstantResolverProvider
{

	public function __construct(private ConstantResolver $constantResolver)
	{
	}

	public function getConstantResolver(): ConstantResolver
	{
		return $this->constantResolver;
	}

}
