<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use function array_merge;
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
				$isSuperType = $theirParameter->getType()->acceptsWithReason($ourParameterType, true);
			} else {
				$isSuperType = new AcceptsResult($theirParameter->getType()->isSuperTypeOf($ourParameterType), []);
			}

			if ($isSuperType->maybe()) {
				$verbosity = VerbosityLevel::getRecommendedLevelByType($theirParameter->getType(), $ourParameterType);
				$isSuperType = new AcceptsResult($isSuperType->result, array_merge($isSuperType->reasons, [
					sprintf(
						'Type %s of parameter %s of passed callable needs to be same or wider than parameter type %s of accepting callable.',
						$theirParameter->getType()->describe($verbosity),
						$parameterDescription,
						$ourParameterType->describe($verbosity),
					),
				]));
			}

			if ($result === null) {
				$result = $isSuperType;
			} else {
				$result = $result->and($isSuperType);
			}
		}

		foreach ($ourParameters as $i => $ourParameter) {
			$parameterDescription = $ourParameter->getName() === '' ? sprintf('#%d', $i + 1) : sprintf('#%d $%s', $i + 1, $ourParameter->getName());
			if (!isset($theirParameters[$i])) {
				if ($ourParameter->isOptional()) {
					continue;
				}

				$accepts = new AcceptsResult(TrinaryLogic::createNo(), [
					sprintf(
						'Accepting callable has a required parameter %s but the passed callable does not have that parameter. This can trigger a fatal error for internal functions and methods which do not accept extra arguments.',
						$parameterDescription,
					),
				]);
				if ($result === null) {
					$result = $accepts;
				} else {
					$result = $result->and($accepts);
				}
			}
		}

		$theirReturnType = $theirs->getReturnType();
		if ($treatMixedAsAny) {
			$isReturnTypeSuperType = $ours->getReturnType()->acceptsWithReason($theirReturnType, true);
		} else {
			$isReturnTypeSuperType = new AcceptsResult($ours->getReturnType()->isSuperTypeOf($theirReturnType), []);
		}

		if ($result === null) {
			$result = $isReturnTypeSuperType;
		} else {
			$result = $result->and($isReturnTypeSuperType);
		}

		return $result;
	}

}
