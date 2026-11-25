<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\SideEffects;
use PHPStan\Type\DynamicFunctionSideEffectsExtension;
use function in_array;

/**
 * print_r(), var_export() and highlight_string() print only when their $return
 * argument is false.
 */
#[AutowiredService]
final class ReturnArgumentSideEffectsExtension implements DynamicFunctionSideEffectsExtension
{

	private const RETURN_PARAMETER_POSITION = 1;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['print_r', 'var_export', 'highlight_string'], true);
	}

	public function getSideEffectsFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?SideEffects
	{
		$returnArg = self::findArg($functionCall->getArgs(), 'return', self::RETURN_PARAMETER_POSITION);
		if ($returnArg === null || !$scope->getType($returnArg->value)->toBoolean()->isTrue()->yes()) {
			return null;
		}

		return SideEffects::none();
	}

	/**
	 * @param Arg[] $args
	 */
	public static function findArg(array $args, string $name, int $position): ?Arg
	{
		foreach ($args as $i => $arg) {
			if ($arg->unpack) {
				return null;
			}
			if ($arg->name !== null) {
				if ($arg->name->toString() === $name) {
					return $arg;
				}
				continue;
			}
			if ($i === $position) {
				return $arg;
			}
		}

		return null;
	}

}
