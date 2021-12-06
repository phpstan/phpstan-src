<?php declare(strict_types = 1);

namespace PHPStan\Type\Traits;

use PHPStan\Reflection\ClassMemberAccessAnswerer;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\TrivialParametersAcceptor;
use PHPStan\TrinaryLogic;

trait MaybeCallableTypeTrait
{

	public function isCallable(): TrinaryLogic
	{
		return TrinaryLogic::createMaybe();
	}

	/**
	 * @return ParametersAcceptor[]
	 */
	public function getCallableParametersAcceptors(ClassMemberAccessAnswerer $scope): array
	{
		return [new TrivialParametersAcceptor()];
	}

}
