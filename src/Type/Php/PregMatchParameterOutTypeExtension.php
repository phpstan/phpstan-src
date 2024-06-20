<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParameterReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\FunctionParameterOutTypeExtension;
use PHPStan\Type\Type;
use function in_array;
use function strtolower;

final class PregMatchParameterOutTypeExtension implements FunctionParameterOutTypeExtension
{

	public function __construct(
		private RegexArrayShapeMatcher $regexShapeMatcher,
	)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection, ParameterReflection $parameter): bool
	{
		return in_array(strtolower($functionReflection->getName()), ['preg_match'], true) && $parameter->getName() === 'matches';
	}

	public function getParameterOutTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $funcCall, ParameterReflection $parameter, Scope $scope): ?Type
	{
		$args = $funcCall->getArgs();
		$patternArg = $args[0] ?? null;
		$matchesArg = $args[2] ?? null;
		$flagsArg = $args[3] ?? null;

		if (
			$patternArg === null || $matchesArg === null
		) {
			return null;
		}

		$patternType = $scope->getType($patternArg->value);
		$flagsType = null;
		if ($flagsArg !== null) {
			$flagsType = $scope->getType($flagsArg->value);
		}

		return $this->regexShapeMatcher->matchType($patternType, $flagsType, TrinaryLogic::createMaybe());
	}

}
