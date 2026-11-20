<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use function array_values;
use function count;
use function is_string;
use function ksort;
use function substr;

/**
 * Normalizes an array callback (arrow function, single-return closure,
 * first-class callable or constant-string callable) into a list of
 * {@see ArrayCallbackPredicate}, and assigns the element value/key types into a
 * scope so the predicate can be evaluated against them. Shared by
 * `array_filter` and the `array_all` / `array_any` type-specifying extensions.
 */
#[AutowiredService]
final class ArrayPredicateCallbackResolver
{

	/**
	 * Returns the normalized predicates, or null when the callback form cannot
	 * be analysed (a variable holding a closure, a by-ref/variadic parameter, a
	 * dynamic name, etc.).
	 *
	 * @return list<ArrayCallbackPredicate>|null
	 */
	public function resolve(MutatingScope $scope, Expr $callbackArg, ArrayCallbackParameterMapping $mapping): ?array
	{
		if ($callbackArg instanceof ArrowFunction) {
			$predicate = $this->fromParams($callbackArg->params, $mapping, $callbackArg->expr);
			return $predicate === null ? null : [$predicate];
		}

		if (
			$callbackArg instanceof Closure
			&& count($callbackArg->stmts) === 1
			&& $callbackArg->stmts[0] instanceof Return_
			&& $callbackArg->stmts[0]->expr !== null
		) {
			$predicate = $this->fromParams($callbackArg->params, $mapping, $callbackArg->stmts[0]->expr);
			return $predicate === null ? null : [$predicate];
		}

		if (
			($callbackArg instanceof FuncCall || $callbackArg instanceof MethodCall || $callbackArg instanceof StaticCall)
			&& $callbackArg->isFirstClassCallable()
		) {
			[$args, $itemVar, $keyVar] = $this->createDummyArgs($mapping);
			$expr = clone $callbackArg;
			$expr->args = $args;

			return [new ArrayCallbackPredicate($itemVar, $keyVar, $expr)];
		}

		$constantStrings = $scope->getType($callbackArg)->getConstantStrings();
		if (count($constantStrings) > 0) {
			[$args, $itemVar, $keyVar] = $this->createDummyArgs($mapping);

			$predicates = [];
			foreach ($constantStrings as $constantString) {
				$funcName = self::createFunctionName($constantString->getValue());
				if ($funcName === null) {
					$predicates[] = new ArrayCallbackPredicate($itemVar, $keyVar, null);
					continue;
				}

				$predicates[] = new ArrayCallbackPredicate($itemVar, $keyVar, new FuncCall($funcName, $args));
			}

			return $predicates;
		}

		return null;
	}

	/**
	 * Assigns the element value/key types onto the scope for the predicate's
	 * variables, returning the new scope along with the variable names that were
	 * assigned (null when the callback does not receive that value or key).
	 *
	 * @return array{MutatingScope, ?string, ?string}
	 */
	public function assignPredicateVariables(MutatingScope $scope, ArrayCallbackPredicate $predicate, Type $itemType, Type $keyType): array
	{
		$itemVarName = null;
		$itemVar = $predicate->getItemVar();
		if ($itemVar !== null) {
			if (!is_string($itemVar->name)) {
				throw new ShouldNotHappenException();
			}
			$itemVarName = $itemVar->name;
			$scope = $scope->assignVariable($itemVarName, $itemType, new MixedType(), TrinaryLogic::createYes());
		}

		$keyVarName = null;
		$keyVar = $predicate->getKeyVar();
		if ($keyVar !== null) {
			if (!is_string($keyVar->name)) {
				throw new ShouldNotHappenException();
			}
			$keyVarName = $keyVar->name;
			$scope = $scope->assignVariable($keyVarName, $keyType, new MixedType(), TrinaryLogic::createYes());
		}

		return [$scope, $itemVarName, $keyVarName];
	}

	/**
	 * @param Param[] $params
	 */
	private function fromParams(array $params, ArrayCallbackParameterMapping $mapping, Expr $expr): ?ArrayCallbackPredicate
	{
		if (count($params) === 0) {
			return null;
		}

		$itemVar = null;
		if ($mapping->getItemPosition() !== null) {
			$var = $this->paramVariable($params, $mapping->getItemPosition());
			if ($var === false) {
				return null;
			}
			$itemVar = $var;
		}

		$keyVar = null;
		if ($mapping->getKeyPosition() !== null) {
			$var = $this->paramVariable($params, $mapping->getKeyPosition());
			if ($var === false) {
				return null;
			}
			$keyVar = $var;
		}

		return new ArrayCallbackPredicate($itemVar, $keyVar, $expr);
	}

	/**
	 * Returns the variable at the given parameter position, null when the
	 * callback declares no such parameter, or false when the parameter cannot be
	 * used for narrowing (by-ref, variadic or non-variable/dynamic name).
	 *
	 * @param Param[] $params
	 */
	private function paramVariable(array $params, int $position): Variable|false|null
	{
		if (!isset($params[$position])) {
			return null;
		}

		$param = $params[$position];
		if ($param->byRef || $param->variadic || !$param->var instanceof Variable || !is_string($param->var->name)) {
			return false;
		}

		return $param->var;
	}

	/**
	 * @return array{list<Arg>, ?Variable, ?Variable}
	 */
	private function createDummyArgs(ArrayCallbackParameterMapping $mapping): array
	{
		$itemPosition = $mapping->getItemPosition();
		$keyPosition = $mapping->getKeyPosition();

		$itemVar = $itemPosition !== null ? new Variable('item') : null;
		$keyVar = $keyPosition !== null ? new Variable('key') : null;

		$args = [];
		if ($itemVar !== null) {
			$args[$itemPosition] = new Arg($itemVar);
		}
		if ($keyVar !== null) {
			$args[$keyPosition] = new Arg($keyVar);
		}
		ksort($args);

		return [array_values($args), $itemVar, $keyVar];
	}

	private static function createFunctionName(string $funcName): ?Name
	{
		if ($funcName === '') {
			return null;
		}

		if ($funcName[0] === '\\') {
			$funcName = substr($funcName, 1);

			if ($funcName === '') {
				return null;
			}

			return new Name\FullyQualified($funcName);
		}

		return new Name($funcName);
	}

}
