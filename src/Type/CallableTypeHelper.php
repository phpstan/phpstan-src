<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;

class CallableTypeHelper
{

	public static function isParametersAcceptorSuperTypeOf(
		ParametersAcceptor $ours,
		ParametersAcceptor $theirs,
		bool $treatMixedAsAny
	): TrinaryLogic
	{
		$theirParameters = $theirs->getParameters();
		$ourParameters = $ours->getParameters();
		if (count($ourParameters) === 1 && $ourParameters[0]->getType() instanceof NeverType) {
			//a parameter with NEVER type is impossible to satisfy, meaning that such acceptors are not callable and therefore can accept anything with any number of arguments
			return TrinaryLogic::createYes();
		}

		$result = null;
		foreach ($theirParameters as $i => $theirParameter) {
			if (!isset($ourParameters[$i])) {
				if ($theirParameter->isOptional()) {
					continue;
				}

				return TrinaryLogic::createNo();
			}

			$ourParameter = $ourParameters[$i];
			$ourParameterType = $ourParameter->getType();
			if ($treatMixedAsAny) {
				$isSuperType = $theirParameter->getType()->accepts($ourParameterType, true);
			} else {
				$isSuperType = $theirParameter->getType()->isSuperTypeOf($ourParameterType);
			}
			if ($result === null) {
				$result = $isSuperType;
			} else {
				$result = $result->and($isSuperType);
			}
		}

		$theirReturnType = $theirs->getReturnType();
		if ($treatMixedAsAny) {
			$isReturnTypeSuperType = $ours->getReturnType()->accepts($theirReturnType, true);
		} else {
			$isReturnTypeSuperType = $ours->getReturnType()->isSuperTypeOf($theirReturnType);
		}
		if ($result === null) {
			$result = $isReturnTypeSuperType;
		} else {
			$result = $result->and($isReturnTypeSuperType);
		}

		return $result;
	}

}
