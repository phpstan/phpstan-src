<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrowFunction;
use PhpParser\Node\Expr\Closure;
use PhpParser\Node\Expr\Error;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\StaticCall;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Name;
use PhpParser\Node\Stmt\Return_;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;
use function is_string;
use function strtolower;
use function substr;

#[AutowiredService]
final class ArrayAllFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		$name = strtolower($functionReflection->getName());

		return ($name === 'array_all' && $context->truthy())
			|| ($name === 'array_any' && $context->falsey());
	}

	public function specifyTypes(
		FunctionReflection $functionReflection,
		FuncCall $node,
		Scope $scope,
		TypeSpecifierContext $context,
	): SpecifiedTypes
	{
		$args = $node->getArgs();
		if (count($args) < 2) {
			return new SpecifiedTypes();
		}

		$arrayArg = $args[0]->value;
		$callbackArg = $args[1]->value;
		$arrayType = $scope->getType($arrayArg);

		$useTruthyFilter = strtolower($functionReflection->getName()) === 'array_all';

		$narrowedType = $this->getNarrowedArrayType($scope, $arrayType, $callbackArg, $useTruthyFilter);
		if ($narrowedType === null) {
			return new SpecifiedTypes();
		}

		return $this->typeSpecifier->create(
			$arrayArg,
			$narrowedType,
			TypeSpecifierContext::createTruthy(),
			$scope,
		)->setRootExpr($node);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	private function getNarrowedArrayType(Scope $scope, Type $arrayType, Expr $callbackArg, bool $useTruthyFilter): ?Type
	{
		$callbackInfo = $this->extractCallbackInfo($scope, $callbackArg);
		if ($callbackInfo === null) {
			return null;
		}

		[$itemVar, $keyVar, $expr] = $callbackInfo;

		return $this->narrowArrayType($scope, $arrayType, $itemVar, $keyVar, $expr, $useTruthyFilter);
	}

	/**
	 * @return array{Error|Variable|null, Error|Variable|null, Expr}|null
	 */
	private function extractCallbackInfo(Scope $scope, Expr $callbackArg): ?array
	{
		if ($callbackArg instanceof Closure && count($callbackArg->stmts) === 1 && count($callbackArg->params) > 0) {
			$statement = $callbackArg->stmts[0];
			if ($statement instanceof Return_ && $statement->expr !== null) {
				$itemVar = $callbackArg->params[0]->var;
				$keyVar = $callbackArg->params[1]->var ?? null;
				return [$itemVar, $keyVar, $statement->expr];
			}
		} elseif ($callbackArg instanceof ArrowFunction && count($callbackArg->params) > 0) {
			$itemVar = $callbackArg->params[0]->var;
			$keyVar = $callbackArg->params[1]->var ?? null;
			return [$itemVar, $keyVar, $callbackArg->expr];
		} elseif (
			($callbackArg instanceof FuncCall || $callbackArg instanceof MethodCall || $callbackArg instanceof StaticCall)
			&& $callbackArg->isFirstClassCallable()
		) {
			$itemVar = new Variable('__item__');
			$args = [new Arg($itemVar)];
			$expr = clone $callbackArg;
			$expr->args = $args;
			return [$itemVar, null, $expr];
		}

		$constantStrings = $scope->getType($callbackArg)->getConstantStrings();
		if (count($constantStrings) > 0) {
			$itemVar = new Variable('__item__');
			$args = [new Arg($itemVar)];
			foreach ($constantStrings as $constantString) {
				$funcName = self::createFunctionName($constantString->getValue());
				if ($funcName === null) {
					return null;
				}
				return [$itemVar, null, new FuncCall($funcName, $args)];
			}
		}

		return null;
	}

	private function narrowArrayType(Scope $scope, Type $arrayType, Error|Variable|null $itemVar, Error|Variable|null $keyVar, Expr $expr, bool $useTruthyFilter): ?Type
	{
		if (!$scope instanceof MutatingScope) {
			throw new ShouldNotHappenException();
		}

		$constantArrays = $arrayType->getConstantArrays();
		if (count($constantArrays) > 0) {
			$results = [];
			foreach ($constantArrays as $constantArray) {
				$builder = ConstantArrayTypeBuilder::createEmpty();
				$optionalKeys = $constantArray->getOptionalKeys();
				foreach ($constantArray->getKeyTypes() as $i => $keyType) {
					$itemType = $constantArray->getValueTypes()[$i];
					$narrowed = $this->narrowKeyAndItemType($scope, $keyType, $itemType, $itemVar, $keyVar, $expr, $useTruthyFilter);
					if ($narrowed === null) {
						return null;
					}
					[$newKeyType, $newItemType] = $narrowed;
					$optional = in_array($i, $optionalKeys, true);
					if ($newKeyType instanceof NeverType || $newItemType instanceof NeverType) {
						if (!$optional) {
							$results[] = new ConstantArrayType([], []);
						}
						continue;
					}
					$builder->setOffsetValueType($newKeyType, $newItemType, $optional);
				}
				$results[] = $builder->getArray();
			}

			return TypeCombinator::union(...$results);
		}

		$narrowed = $this->narrowKeyAndItemType($scope, $arrayType->getIterableKeyType(), $arrayType->getIterableValueType(), $itemVar, $keyVar, $expr, $useTruthyFilter);
		if ($narrowed === null) {
			return null;
		}
		[$newKeyType, $newItemType] = $narrowed;

		if ($newItemType instanceof NeverType || $newKeyType instanceof NeverType) {
			return new ConstantArrayType([], []);
		}

		return new ArrayType($newKeyType, $newItemType);
	}

	/**
	 * @return array{Type, Type}|null
	 */
	private function narrowKeyAndItemType(MutatingScope $scope, Type $keyType, Type $itemType, Error|Variable|null $itemVar, Error|Variable|null $keyVar, Expr $expr, bool $useTruthyFilter): ?array
	{
		$itemVarName = null;
		if ($itemVar !== null) {
			if (!$itemVar instanceof Variable || !is_string($itemVar->name)) {
				return null;
			}
			$itemVarName = $itemVar->name;
			$scope = $scope->assignVariable($itemVarName, $itemType, new MixedType(), TrinaryLogic::createYes());
		}

		$keyVarName = null;
		if ($keyVar !== null) {
			if (!$keyVar instanceof Variable || !is_string($keyVar->name)) {
				return null;
			}
			$keyVarName = $keyVar->name;
			$scope = $scope->assignVariable($keyVarName, $keyType, new MixedType(), TrinaryLogic::createYes());
		}

		if ($useTruthyFilter) {
			$filteredScope = $scope->filterByTruthyValue($expr);
		} else {
			$filteredScope = $scope->filterByFalseyValue($expr);
		}

		return [
			$keyVarName !== null ? $filteredScope->getVariableType($keyVarName) : $keyType,
			$itemVarName !== null ? $filteredScope->getVariableType($itemVarName) : $itemType,
		];
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
