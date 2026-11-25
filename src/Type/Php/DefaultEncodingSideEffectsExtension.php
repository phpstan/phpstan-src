<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\SideEffects;
use PHPStan\Type\DynamicFunctionSideEffectsExtension;
use function str_starts_with;

/**
 * The mbstring and iconv functions read the default encoding from global
 * state only when they are not given an encoding: mb_strlen($s, 'UTF-8')
 * is pure, mb_strlen($s) and mb_strlen($s, null) depend on mb_internal_encoding().
 * mb_scrub() also reads the substitute character, so it is left out.
 */
#[AutowiredService]
final class DefaultEncodingSideEffectsExtension implements DynamicFunctionSideEffectsExtension
{

	private const ENCODING_PARAMETER_NAME = 'encoding';

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		$name = $functionReflection->getName();
		if ($name === 'mb_scrub') {
			return false;
		}

		$effects = $functionReflection->getSideEffects()->getEffectNames();
		if ($effects === [SideEffects::READS_GLOBAL_MBSTRING]) {
			return $this->findEncodingParameterPosition($functionReflection) !== null;
		}

		return str_starts_with($name, 'iconv_')
			&& $effects === [SideEffects::READS_GLOBAL]
			&& $this->findEncodingParameterPosition($functionReflection) !== null;
	}

	public function getSideEffectsFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?SideEffects
	{
		$position = $this->findEncodingParameterPosition($functionReflection);
		if ($position === null) {
			return null;
		}

		$encodingArg = ReturnArgumentSideEffectsExtension::findArg($functionCall->getArgs(), self::ENCODING_PARAMETER_NAME, $position);
		if ($encodingArg === null || !$scope->getType($encodingArg->value)->isNull()->no()) {
			return null;
		}

		return SideEffects::none();
	}

	private function findEncodingParameterPosition(FunctionReflection $functionReflection): ?int
	{
		foreach ($functionReflection->getVariants() as $variant) {
			foreach ($variant->getParameters() as $position => $parameter) {
				if ($parameter->getName() === self::ENCODING_PARAMETER_NAME) {
					return $position;
				}
			}
		}

		return null;
	}

}
