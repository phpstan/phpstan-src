<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\TrinaryLogic;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;

#[AutowiredService]
final class PgDmlDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** @var array<string, int> */
	private array $flagsArgPositions = [
		'pg_insert' => 3,
		'pg_update' => 4,
		'pg_delete' => 3,
		'pg_select' => 3,
	];

	public function __construct(private BitwiseFlagHelper $bitwiseFlagAnalyser)
	{
	}

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return array_key_exists($functionReflection->getName(), $this->flagsArgPositions);
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): ?Type
	{
		$functionName = $functionReflection->getName();
		$args = $functionCall->getArgs();
		$defaultReturnType = ParametersAcceptorSelector::selectFromArgs(
			$scope,
			$args,
			$functionReflection->getVariants(),
		)->getReturnType();

		$flagsArgPosition = $this->flagsArgPositions[$functionName];
		if (!isset($args[$flagsArgPosition])) {
			// default flags are PGSQL_DML_EXEC
			return $this->getTypeFromFlags($functionName, TrinaryLogic::createNo(), TrinaryLogic::createYes(), $defaultReturnType);
		}

		$flagsExpr = $args[$flagsArgPosition]->value;

		return $this->getTypeFromFlags(
			$functionName,
			$this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($flagsExpr, $scope, 'PGSQL_DML_STRING'),
			$this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($flagsExpr, $scope, 'PGSQL_DML_EXEC'),
			$defaultReturnType,
		);
	}

	private function getTypeFromFlags(string $functionName, TrinaryLogic $containsString, TrinaryLogic $containsExec, Type $defaultReturnType): ?Type
	{
		if ($functionName === 'pg_insert') {
			// with PGSQL_DML_EXEC the result object is returned even when PGSQL_DML_STRING is also set
			if ($containsExec->yes()) {
				return TypeCombinator::remove(
					TypeCombinator::remove($defaultReturnType, new StringType()),
					new ConstantBooleanType(true),
				);
			}
			if (!$containsExec->no()) {
				return null;
			}
			if ($containsString->yes()) {
				return TypeCombinator::union(new StringType(), new ConstantBooleanType(false));
			}
			if ($containsString->no()) {
				return new BooleanType();
			}

			return null;
		}

		if ($containsString->yes()) {
			return TypeCombinator::union(new StringType(), new ConstantBooleanType(false));
		}
		if (!$containsString->no()) {
			return null;
		}

		return TypeCombinator::remove($defaultReturnType, new StringType());
	}

}
