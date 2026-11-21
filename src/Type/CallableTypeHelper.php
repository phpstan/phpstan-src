<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Reflection\Callables\CallableParametersAcceptor;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use function array_key_exists;
use function array_keys;
use function array_merge;
use function count;
use function sprintf;

#[ShadowedByTurboExtension(implementation: __DIR__ . '/../../turbo-ext/src/CallableTypeHelper.cpp')]
final class CallableTypeHelper
{

	public static function inferReturnTemplateTypes(Type $expected, Type $received, TemplateTypeMap $typeMap): TemplateTypeMap
	{
		if (
			!$received instanceof NeverType
			|| !$received->isExplicit()
			|| $expected instanceof TemplateType
		) {
			return $typeMap->union($expected->inferTemplateTypes($received));
		}

		$classes = $expected->getObjectClassReflections();
		if (count($classes) !== 1 || !$classes[0]->isGeneric()) {
			return $typeMap->union($expected->inferTemplateTypes($received));
		}

		// A callable that never returns cannot contribute a value of any of
		// the generic return object's types. Keep any inference from its parameters.
		$types = [];
		foreach ($expected->getReferencedTemplateTypes(TemplateTypeVariance::createCovariant()) as $reference) {
			$variance = $reference->getPositionVariance();
			$template = $reference->getType();
			$name = $template->getName();
			if (
				(!$variance->covariant() && !$variance->invariant())
				|| $template->getDefault() !== null
				|| $typeMap->hasType($name)
			) {
				continue;
			}

			$types[$name] = $received;
		}

		return $typeMap->union(new TemplateTypeMap($types));
	}

	public static function isParametersAcceptorSuperTypeOf(
		CallableParametersAcceptor $ours,
		CallableParametersAcceptor $theirs,
		bool $treatMixedAsAny,
		bool $strictTypes = true,
	): IsSuperTypeOfResult
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
			foreach (array_keys($ourParameters) as $i) {
				if (array_key_exists($i, $theirParameters)) {
					continue;
				}
				$theirParameters[] = $lastParameter;
			}
		}

		$result = IsSuperTypeOfResult::createYes();
		foreach ($theirParameters as $i => $theirParameter) {
			$parameterDescription = $theirParameter->getName() === '' ? sprintf('#%d', $i + 1) : sprintf('#%d $%s', $i + 1, $theirParameter->getName());
			if (!isset($ourParameters[$i])) {
				if ($theirParameter->isOptional()) {
					continue;
				}

				$accepts = new IsSuperTypeOfResult(TrinaryLogic::createNo(), [
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
				$accepts = new IsSuperTypeOfResult(TrinaryLogic::createNo(), [
					sprintf(
						'Parameter %s of passed callable is required but the parameter of accepting callable is optional. It might be called without it.',
						$parameterDescription,
					),
				]);
				$result = $result->and($accepts);
			}

			if ($treatMixedAsAny) {
				$isSuperType = $theirParameter->getType()->accepts($ourParameterType, $strictTypes);
				$isSuperType = new IsSuperTypeOfResult($isSuperType->result, $isSuperType->reasons);
			} else {
				$isSuperType = $theirParameter->getType()->isSuperTypeOf($ourParameterType);
			}

			if ($isSuperType->maybe()) {
				$verbosity = VerbosityLevel::getRecommendedLevelByType($theirParameter->getType(), $ourParameterType);
				$isSuperType = new IsSuperTypeOfResult($isSuperType->result, array_merge($isSuperType->reasons, [
					sprintf(
						'Type %s of parameter %s of passed callable needs to be same or wider than parameter type %s of accepting callable.',
						$theirParameter->getType()->describe($verbosity),
						$parameterDescription,
						$ourParameterType->describe($verbosity),
					),
				]), $isSuperType->lazyReasons);
			}

			$result = $result->and($isSuperType);
		}

		if (!$treatMixedAsAny && $theirParameterCount < $ourParameterCount) {
			$result = $result->and(IsSuperTypeOfResult::createMaybe());
		}

		$theirReturnType = $theirs->getReturnType();
		if ($treatMixedAsAny) {
			$isReturnTypeSuperType = $ours->getReturnType()->accepts($theirReturnType, true);
			$isReturnTypeSuperType = new IsSuperTypeOfResult($isReturnTypeSuperType->result, $isReturnTypeSuperType->reasons);
		} else {
			$isReturnTypeSuperType = $ours->getReturnType()->isSuperTypeOf($theirReturnType);
		}

		$pure = $ours->isPure();
		if ($pure->yes()) {
			$result = $result->and(IsSuperTypeOfResult::createFromTrinaryLogic($theirs->isPure()));
		} elseif ($pure->no()) {
			$result = $result->and(IsSuperTypeOfResult::createFromTrinaryLogic($theirs->isPure()->negate()));
		}

		$ourStatic = $ours->isStaticClosure();
		if ($ourStatic->yes()) {
			$result = $result->and(IsSuperTypeOfResult::createFromTrinaryLogic($theirs->isStaticClosure()));
		} elseif ($ourStatic->no()) {
			$result = $result->and(IsSuperTypeOfResult::createFromTrinaryLogic($theirs->isStaticClosure()->negate()));
		}

		return $result->and($isReturnTypeSuperType);
	}

}
