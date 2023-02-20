<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use function sprintf;

class CallableTypeHelper
{

	public static function isParametersAcceptorSuperTypeOf(
		ParametersAcceptor $ours,
		ParametersAcceptor $theirs,
		bool $treatMixedAsAny,
	): AcceptsResult
	{
		$theirParameters = $theirs->getParameters();
		$ourParameters = $ours->getParameters();

		$result = null;
		foreach ($theirParameters as $i => $theirParameter) {
			$parameterDescription = $theirParameter->getName() === '' ? sprintf('#%d', $i + 1) : sprintf('#%d $%s', $i + 1, $theirParameter->getName());
			if (!isset($ourParameters[$i])) {
				if ($theirParameter->isOptional()) {
					continue;
				}

				$accepts = new AcceptsResult(TrinaryLogic::createNo(), [
					sprintf(
						'Parameter %s of passed callable is required but accepting callable does not have that parameter. It will be called without it.',
						$parameterDescription,
					),
				]);
				if ($result === null) {
					$result = $accepts;
				} else {
					$result = $result->and($accepts);
				}
				continue;
			}

			$ourParameter = $ourParameters[$i];
			$ourParameterType = $ourParameter->getType();

			if ($ourParameter->isOptional() && !$theirParameter->isOptional()) {
				$accepts = new AcceptsResult(TrinaryLogic::createNo(), [
					sprintf(
						'Parameter %s of passed callable is required but the parameter of accepting callable is optional. It might be called without it.',
						$parameterDescription,
					),
				]);
				if ($result === null) {
					$result = $accepts;
				} else {
					$result = $result->and($accepts);
				}
			}

			if ($treatMixedAsAny) {
				$isSuperType = $theirParameter->getType()->accepts($ourParameterType, true);
			} else {
				$isSuperType = $theirParameter->getType()->isSuperTypeOf($ourParameterType);
			}

			if ($isSuperType->maybe()) {
				$verbosity = VerbosityLevel::getRecommendedLevelByType($theirParameter->getType(), $ourParameterType);
				$isSuperType = new AcceptsResult($isSuperType, [
					sprintf(
						'Type %s of parameter %s of passed callable needs to same or wider than parameter type %s of accepting callable.',
						$theirParameter->getType()->describe($verbosity),
						$parameterDescription,
						$ourParameterType->describe($verbosity),
					),
				]);
			} else {
				$isSuperType = new AcceptsResult($isSuperType, []);
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

		$isReturnTypeSuperType = new AcceptsResult($isReturnTypeSuperType, []);
		if ($result === null) {
			$result = $isReturnTypeSuperType;
		} else {
			$result = $result->and($isReturnTypeSuperType);
		}

		return $result;
	}

}
