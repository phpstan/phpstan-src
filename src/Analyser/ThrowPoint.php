<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PHPStan\Type\Type;

class ThrowPoint
{

	private MutatingScope $scope;

	private Type $type;

	public function __construct(MutatingScope $scope, Type $type)
	{
		$this->scope = $scope;
		$this->type = $type;
	}

	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function getType(): Type
	{
		return $this->type;
	}

}
