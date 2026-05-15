<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\MethodCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\MethodReflection;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\DynamicMethodReturnTypeExtension;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function count;

#[AutowiredService]
final class PdoStatementFetchAllReturnTypeExtension implements DynamicMethodReturnTypeExtension
{

	public function getClass(): string
	{
		return 'PDOStatement';
	}

	public function isMethodSupported(MethodReflection $methodReflection): bool
	{
		return $methodReflection->getName() === 'fetchAll';
	}

	public function getTypeFromMethodCall(
		MethodReflection $methodReflection,
		MethodCall $methodCall,
		Scope $scope,
	): ?Type
	{
		$args = $methodCall->getArgs();
		if (count($args) < 1) {
			return null;
		}

		$modeType = $scope->getType($args[0]->value);
		$constantIntegers = TypeUtils::getConstantIntegers($modeType);

		if (count($constantIntegers) === 0) {
			return null;
		}

		foreach ($constantIntegers as $constantInteger) {
			$mode = $constantInteger->getValue();
			if ($mode === 0 || ($mode & 0xFFFF) === \PDO::FETCH_KEY_PAIR || ($mode & \PDO::FETCH_GROUP) !== 0) {
				return null;
			}
		}

		$variant = ParametersAcceptorSelector::selectFromArgs($scope, $args, $methodReflection->getVariants());
		$returnType = $variant->getReturnType();

		$listType = TypeCombinator::intersect(
			new ArrayType(new IntegerType(), new MixedType()),
			new AccessoryArrayListType(),
		);

		if (!$returnType->isFalse()->no()) {
			return TypeCombinator::union($listType, new ConstantBooleanType(false));
		}

		return $listType;
	}

}
