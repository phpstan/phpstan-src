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
use function array_filter;
use function array_values;
use function count;
use function in_array;
use function ltrim;
use function str_contains;
use function strrpos;
use function substr;

/**
 * The PCRE functions warn when the pattern does not compile, so a call with a
 * pattern that is not known to compile has the warns effect, such as
 * preg_match($pattern, '') to validate a pattern under set_error_handler().
 * A call with a pattern that compiles is not there for the error it leaves
 * for preg_last_error().
 * A pattern with the u modifier compiles only where PCRE supports UTF-8, so
 * a call with it can warn, such as preg_match('//u', '') to find out
 * whether it does.
 */
#[AutowiredService]
final class PregSideEffectsExtension implements DynamicFunctionSideEffectsExtension
{

	private const PATTERN_PARAMETER_POSITION = 0;

	private const BRACKET_DELIMITERS = ['(' => ')', '[' => ']', '{' => '}', '<' => '>'];

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), [
			'preg_match',
			'preg_match_all',
			'preg_replace',
			'preg_filter',
			'preg_grep',
			'preg_split',
			'preg_replace_callback',
		], true);
	}

	public function getSideEffectsFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): SideEffects
	{
		$sideEffects = $functionReflection->getSideEffects();
		$patternArg = ReturnArgumentSideEffectsExtension::findArg($functionCall->getArgs(), 'pattern', self::PATTERN_PARAMETER_POSITION);
		if ($patternArg !== null && $this->compiles($scope->getType($patternArg->value))) {
			// such a call can still fail on the subject and leave an error for
			// preg_last_error(), which the scope forgets, but it is not called for that
			return SideEffects::create(
				array_values(array_filter($sideEffects->getEffectNames(), static fn (string $effect): bool => $effect !== SideEffects::WRITES_GLOBAL_ERROR_PREG)),
				TrinaryLogic::createNo(),
				$sideEffects->getCalledParameters(),
				$sideEffects->getWrittenParameters(),
				$sideEffects->isDeterministic()->no(),
			);
		}

		return SideEffects::create(
			[...$sideEffects->getEffectNames(), SideEffects::WARNS],
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

			if (str_contains($this->getModifiers($pattern->getValue()), 'u')) {
				return false;
			}
		}

		return true;
	}

	private function getModifiers(string $pattern): string
	{
		$pattern = ltrim($pattern);
		$delimiter = $pattern[0];
		$endDelimiterPosition = strrpos($pattern, self::BRACKET_DELIMITERS[$delimiter] ?? $delimiter);
		if ($endDelimiterPosition === false) {
			return '';
		}

		return substr($pattern, $endDelimiterPosition + 1);
	}

}
