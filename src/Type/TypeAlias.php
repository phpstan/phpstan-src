<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Analyser\NameScope;
use PHPStan\PhpDoc\TypeStringResolver;

class TypeAlias
{

	private string $aliasString;

	private NameScope $nameScope;

	private ?Type $resolvedType = null;

	public function __construct(
		string $typeString,
		NameScope $nameScope
	)
	{
		$this->aliasString = $typeString;
		$this->nameScope = $nameScope;
	}

	public function resolve(TypeStringResolver $typeStringResolver): Type
	{
		if ($this->resolvedType === null) {
			$this->resolvedType = $typeStringResolver->resolve(
				$this->aliasString,
				$this->nameScope
			);
		}

		return $this->resolvedType;
	}

}
