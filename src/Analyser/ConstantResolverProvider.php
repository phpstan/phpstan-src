<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

interface ConstantResolverProvider
{

	public function getConstantResolver(): ConstantResolver;

}
