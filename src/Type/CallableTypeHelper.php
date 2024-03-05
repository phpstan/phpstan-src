<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\TrinaryLogic;
use function array_key_exists;
use function array_merge;
use function count;
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

		$lastParameter = null;
		foreach ($theirParameters as $theirParameter) {
			$lastParameter = $theirParameter;
		}
		$theirParameterCount = count($theirParameters);
		$ourParameterCount = count($ourParameters);
		if (
			$lastParameter !== null
			&& $lastParameter->isVariadic()
			&& $theirParameterCount < $ourParameterCount
		) {
			foreach ($ourParameters as $i => $ourParameter) {
				if (array_key_exists($i, $theirParameters)) {
					continue;
				}
				$theirParameters[] = $lastParameter;
			}
		}

		$result = AcceptsResult::createYes();
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
				$result = $result->and($accepts);
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
				$result = $result->and($accepts);
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

			$result = $result->and($isSuperType);
		}

		if (!$treatMixedAsAny && $theirParameterCount < $ourParameterCount) {
			$result = $result->and(AcceptsResult::createMaybe());
		}

		$theirReturnType = $theirs->getReturnType();
		if ($treatMixedAsAny) {
			$isReturnTypeSuperType = $ours->getReturnType()->acceptsWithReason($theirReturnType, true);
		} else {
			$isReturnTypeSuperType = new AcceptsResult($ours->getReturnType()->isSuperTypeOf($theirReturnType), []);
		}

		return $result->and($isReturnTypeSuperType);
	}

}
