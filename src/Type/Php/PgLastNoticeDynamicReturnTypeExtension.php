<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

#[AutowiredService]
final class PgLastNoticeDynamicReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	private const NOTICE_LAST = 1;
	private const NOTICE_ALL = 2;
	private const NOTICE_CLEAR = 3;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'pg_last_notice';
	}

	public function getTypeFromFunctionCall(
		FunctionReflection $functionReflection,
		FuncCall $functionCall,
		Scope $scope,
	): Type
	{
		$args = $functionCall->getArgs();
		if (!isset($args[1])) {
			return new StringType();
		}

		$types = [];
		foreach ($scope->getType($args[1]->value)->getConstantScalarValues() as $value) {
			if ($value === self::NOTICE_LAST) {
				$types[] = new StringType();
			} elseif ($value === self::NOTICE_ALL) {
				$types[] = $this->createAllNoticesType();
			} elseif ($value === self::NOTICE_CLEAR) {
				$types[] = new ConstantBooleanType(true);
			} else {
				return $this->createUnknownModeType();
			}
		}

		if ($types === []) {
			return $this->createUnknownModeType();
		}

		return TypeCombinator::union(...$types);
	}

	private function createAllNoticesType(): Type
	{
		return TypeCombinator::intersect(
			new ArrayType(new IntegerType(), new StringType()),
			new AccessoryArrayListType(),
		);
	}

	private function createUnknownModeType(): Type
	{
		return TypeCombinator::union(
			new StringType(),
			$this->createAllNoticesType(),
			new ConstantBooleanType(true),
		);
	}

}
