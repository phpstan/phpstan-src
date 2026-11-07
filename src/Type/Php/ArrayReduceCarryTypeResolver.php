<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\ExprHandler\Helper\ClosureTypeResolver;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\Native\NativeParameterReflection;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\CallableType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;

/**
 * Types the carry of an array_reduce() closure the way a foreach loop types
 * a variable it writes on every pass: the carry starts as the initial value
 * and grows by what the closure returns for it, widened like a loop variable,
 * until what the closure returns adds nothing new.
 */
#[AutowiredService]
final class ArrayReduceCarryTypeResolver
{

	/**
	 * Holds the carry types of two passes in a throwaway scope, so that
	 * they are widened by the very rules that widen a loop variable.
	 */
	private const CARRY_VARIABLE_NAME = '__phpstanArrayReduceCarry';

	public function __construct(
		private ClosureTypeResolver $closureTypeResolver,
	)
	{
	}

	/**
	 * @return array{carry: Type, item: Type, return: Type}|null null when the callback is not a closure or arrow function written in the call
	 */
	public function resolve(FuncCall $functionCall, Scope $scope): ?array
	{
		if (!$scope instanceof MutatingScope) {
			return null;
		}

		$args = $functionCall->getArgs();
		if (!isset($args[0], $args[1]) || $args[0]->unpack || $args[1]->unpack) {
			return null;
		}

		$callback = $args[1]->value;
		if (!$callback instanceof Closure && !$callback instanceof ArrowFunction) {
			return null;
		}
		if (count($callback->params) < 1) {
			return null;
		}
		foreach ($callback->params as $param) {
			if ($param->byRef || $param->variadic) {
				return null;
			}
		}

		$itemType = $scope->getIterableValueType($scope->getType($args[0]->value));
		$carryType = isset($args[2]) ? $scope->getType($args[2]->value) : new NullType();

		for ($count = 0;; $count++) {
			$returnType = $this->getReturnType($scope, $callback, $carryType, $itemType);
			if ($returnType instanceof NeverType && !$returnType->isExplicit()) {
				// the body's inference gave up, which tells nothing about what it returns
				return null;
			}

			$nextCarryType = TypeCombinator::union($carryType, $returnType);
			if ($nextCarryType->equals($carryType)) {
				return ['carry' => $carryType, 'item' => $itemType, 'return' => $returnType];
			}

			if ($count >= NodeScopeResolver::LOOP_SCOPE_ITERATIONS) {
				// no fixed point: a carry type the closure can still outgrow would be unsound
				return null;
			}

			if ($count >= NodeScopeResolver::GENERALIZE_AFTER_ITERATION) {
				$nextCarryType = $this->generalize($scope, $carryType, $nextCarryType);
			}

			$carryType = $nextCarryType;
		}
	}

	public static function createCallbackParameter(string $name, Type $type): NativeParameterReflection
	{
		return new NativeParameterReflection($name, false, $type, PassedByReference::createNo(), false, null);
	}

	private function getReturnType(MutatingScope $scope, Closure|ArrowFunction $callback, Type $carryType, Type $itemType): Type
	{
		$passedToType = new CallableType(
			[
				self::createCallbackParameter('carry', $carryType),
				self::createCallbackParameter('item', $itemType),
			],
			new MixedType(),
			false,
		);

		return $this->closureTypeResolver->getClosureType($scope, $callback, false, $scope->getCurrentExpressionResultStorage(), $passedToType)->getReturnType();
	}

	private function generalize(MutatingScope $scope, Type $previousCarryType, Type $carryType): Type
	{
		$yes = TrinaryLogic::createYes();
		$previousScope = $scope->assignVariable(self::CARRY_VARIABLE_NAME, $previousCarryType, $previousCarryType, $yes);
		$nextScope = $scope->assignVariable(self::CARRY_VARIABLE_NAME, $carryType, $carryType, $yes);

		return $previousScope->generalizeWith($nextScope)->getVariableType(self::CARRY_VARIABLE_NAME);
	}

}
