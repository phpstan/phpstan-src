<?php declare(strict_types = 1);

namespace PHPStan\Tests;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PHPStan\Analyser\Scope;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\SideEffects;
use PHPStan\TrinaryLogic;
use PHPStan\Type\DynamicFunctionSideEffectsExtension;
use PHPStan\Type\DynamicMethodSideEffectsExtension;
use PHPStan\Type\DynamicStaticMethodSideEffectsExtension;
use function array_filter;
use function array_values;
use function count;
use function in_array;

/**
 * A call with arguments has exactly the effects its constant string arguments
 * name, 'none' for none; a call without arguments has the declared effects.
 */
final class EffectsFromArguments
{

	/**
	 * @param Arg[] $args
	 */
	public static function create(array $args, Scope $scope): ?SideEffects
	{
		if (count($args) === 0) {
			return null;
		}

		$effects = [];
		foreach ($args as $arg) {
			foreach ($scope->getType($arg->value)->getConstantStrings() as $string) {
				$effects[] = $string->getValue();
			}
		}

		return SideEffects::create(array_values(array_filter($effects, static fn (string $effect): bool => $effect !== 'none')), TrinaryLogic::createNo());
	}

}

class FunctionSideEffectsFromArguments implements DynamicFunctionSideEffectsExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return in_array($functionReflection->getName(), ['DynamicSideEffectsExtensions\\impureFunction', 'DynamicSideEffectsExtensions\\pureFunction', 'DynamicSideEffectsExtensions\\impureIntFunction'], true);
	}

	public function getSideEffectsFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?SideEffects
	{
		return EffectsFromArguments::create($functionCall->getArgs(), $scope);
	}

}

/** error_reporting() without an argument only reads the setting. */
class ErrorReportingGetterSideEffects implements DynamicFunctionSideEffectsExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'error_reporting';
	}

	public function getSideEffectsFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?SideEffects
	{
		if (count($functionCall->getArgs()) !== 0) {
			return null;
		}

		return SideEffects::create([SideEffects::READS_GLOBAL], TrinaryLogic::createNo());
	}

}

class MethodSideEffectsFromArguments implements DynamicMethodSideEffectsExtension
{

	public function getClass(): string
	{
		return \DynamicSideEffectsExtensions\Box::class;
	}

	public function isMethodSupported(ExtendedMethodReflection $methodReflection): bool
	{
		return true;
	}

	public function getSideEffectsFromMethodCall(ExtendedMethodReflection $methodReflection, MethodCall $methodCall, Scope $scope): ?SideEffects
	{
		return EffectsFromArguments::create($methodCall->getArgs(), $scope);
	}

}

class StaticMethodSideEffectsFromArguments implements DynamicStaticMethodSideEffectsExtension
{

	public function getClass(): string
	{
		return \DynamicSideEffectsExtensions\Box::class;
	}

	public function isStaticMethodSupported(ExtendedMethodReflection $methodReflection): bool
	{
		return true;
	}

	public function getSideEffectsFromStaticMethodCall(ExtendedMethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?SideEffects
	{
		return EffectsFromArguments::create($methodCall->getArgs(), $scope);
	}

}

class ConstructorSideEffectsFromArguments implements DynamicStaticMethodSideEffectsExtension
{

	public function getClass(): string
	{
		return \DynamicSideEffectsExtensions\Made::class;
	}

	public function isStaticMethodSupported(ExtendedMethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === '__construct';
	}

	public function getSideEffectsFromStaticMethodCall(ExtendedMethodReflection $methodReflection, StaticCall $methodCall, Scope $scope): ?SideEffects
	{
		return EffectsFromArguments::create($methodCall->getArgs(), $scope);
	}

}
