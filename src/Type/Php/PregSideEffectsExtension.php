<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use Nette\Utils\RegexpException;
use Nette\Utils\Strings;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\SideEffects;
use PHPStan\TrinaryLogic;
use PHPStan\Type\DynamicFunctionSideEffectsExtension;
use PHPStan\Type\Type;
use function count;
use function in_array;

/**
 * The PCRE functions warn when the pattern does not compile, so a call with a
 * pattern that is not known to compile has the warns effect, such as
 * preg_match($pattern, '') to validate a pattern under set_error_handler().
 */
#[AutowiredService]
final class PregSideEffectsExtension implements DynamicFunctionSideEffectsExtension
{

	private const PATTERN_PARAMETER_POSITION = 0;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'preg_match',
			'preg_match_all',
			'preg_grep',
			'preg_split',
			'preg_replace_callback',
		], true);
	}

	public function getSideEffectsFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?SideEffects
	{
		$patternArg = ReturnArgumentSideEffectsExtension::findArg($functionCall->getArgs(), 'pattern', self::PATTERN_PARAMETER_POSITION);
		if ($patternArg !== null && $this->compiles($scope->getType($patternArg->value))) {
			return null;
		}

		$sideEffects = $functionReflection->getSideEffects();

		return SideEffects::create(
			[...$sideEffects->getEffectNames(), 'warns'],
			TrinaryLogic::createNo(),
			$sideEffects->getCalledParameters(),
			$sideEffects->getWrittenParameters(),
			$sideEffects->isDeterministic()->no(),
		);
	}

	/** Whether every pattern the type can be is a constant string that compiles. */
	private function compiles(Type $patternType): bool
	{
		$patterns = $patternType->getConstantStrings();
		if (count($patterns) === 0) {
			$arrays = $patternType->getConstantArrays();
			if (count($arrays) === 0) {
				return false;
			}
			foreach ($arrays as $array) {
				foreach ($array->getValueTypes() as $valueType) {
					if (!$this->compiles($valueType)) {
						return false;
					}
				}
			}

			return true;
		}

		if (!$patternType->isConstantScalarValue()->yes()) {
			return false;
		}

		foreach ($patterns as $pattern) {
			try {
				Strings::match('', $pattern->getValue());
			} catch (RegexpException) {
				return false;
			}
		}

		return true;
	}

}
