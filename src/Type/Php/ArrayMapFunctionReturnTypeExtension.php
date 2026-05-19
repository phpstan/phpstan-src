<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\AccessoryArrayListType;
use PHPStan\Type\Accessory\AccessoryType;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\DynamicFunctionReturnTypeExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use function array_map;
use function array_reduce;
use function array_slice;
use function count;

#[AutowiredService]
final class ArrayMapFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_map';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		$numArgs = count($args);
		if ($numArgs < 2) {
			return null;
		}

		$singleArrayArgument = !isset($args[2]);
		$callback = $args[0]->value;
		$callableType = $scope->getType($callback);
		$callableIsNull = $callableType->isNull()->yes();

		if ($callableType->isCallable()->yes()) {
			$valueType = $scope->getType(new FuncCall(
				$callback,
				array_map(
					static fn (Node\Arg $arg) => new Node\Arg(new TypeExpr($scope->getType($arg->value)->getIterableValueType())),
					array_slice($args, 1),
				),
			));
		} elseif ($callableIsNull) {
			$arrayBuilder = ConstantArrayTypeBuilder::createEmpty();
			$argTypes = [];
			$areAllSameSize = true;
			$expectedSize = null;
			foreach (array_slice($args, 1) as $index => $arg) {
				$argTypes[$index] = $argType = $scope->getType($arg->value);
				if (!$areAllSameSize || $numArgs === 2) {
					continue;
				}

				$arraySizes = $argType->getArraySize()->getConstantScalarValues();
				if ($arraySizes === []) {
					$areAllSameSize = false;
					continue;
				}

				foreach ($arraySizes as $size) {
					$expectedSize ??= $size;
					if ($expectedSize === $size) {
						continue;
					}

					$areAllSameSize = false;
					continue 2;
				}
			}

			if (!$areAllSameSize) {
				$firstArr = $args[1]->value;
				$identities = [];
				foreach (array_slice($args, 2) as $arg) {
					$identities[] = new Node\Expr\BinaryOp\Identical($firstArr, $arg->value);
				}

				$and = array_reduce(
					$identities,
					static fn (Node\Expr $a, Node\Expr $b) => new Node\Expr\BinaryOp\BooleanAnd($a, $b),
					new Node\Expr\ConstFetch(new Node\Name('true')),
				);
				$areAllSameSize = $scope->getType($and)->isTrue()->yes();
			}

			$addNull = !$areAllSameSize;

			foreach ($argTypes as $index => $argType) {
				$offsetValueType = $argType->getIterableValueType();
				if ($addNull) {
					$offsetValueType = TypeCombinator::addNull($offsetValueType);
				}

				$arrayBuilder->setOffsetValueType(
					new ConstantIntegerType($index),
					$offsetValueType,
				);
			}
			$valueType = $arrayBuilder->getArray();
		} else {
			$valueType = new MixedType();
		}

		$arrayType = $scope->getType($args[1]->value);

		if ($singleArrayArgument) {
			if ($callableIsNull) {
				return $arrayType;
			}
			$constantArrays = $arrayType->getConstantArrays();
			if (count($constantArrays) > 0) {
				$totalCount = TypeCombinator::countConstantArrayValueTypes($constantArrays) * TypeCombinator::countConstantArrayValueTypes([$valueType]);
				if ($totalCount < ConstantArrayTypeBuilder::ARRAY_COUNT_LIMIT) {
					$mappedArrayType = $arrayType->mapValueType(static fn (Type $type): Type => self::resolveCallbackReturnType($scope, $callback, $type));
				} else {
					$mappedArrayType = TypeCombinator::intersect(new ArrayType(
						$arrayType->getIterableKeyType(),
						$valueType,
					), ...$this->getAccessoryTypes($arrayType, $valueType));
				}
			} elseif ($arrayType->isArray()->yes()) {
				$mappedArrayType = TypeCombinator::intersect(new ArrayType(
					$arrayType->getIterableKeyType(),
					$valueType,
				), ...$this->getAccessoryTypes($arrayType, $valueType));
			} else {
				$mappedArrayType = new ArrayType(
					new MixedType(),
					$valueType,
				);
			}
		} else {
			$mappedArrayType = new IntersectionType([new ArrayType(
				IntegerRangeType::createAllGreaterThanOrEqualTo(0),
				$valueType,
			), new AccessoryArrayListType(), ...$this->getAccessoryTypes($arrayType, $valueType)]);
		}

		if ($arrayType->isIterableAtLeastOnce()->yes()) {
			$mappedArrayType = TypeCombinator::intersect($mappedArrayType, new NonEmptyArrayType());
		}

		return $mappedArrayType;
	}

	private static int $cloneCounter = 0;

	private static function resolveCallbackReturnType(Scope $scope, Node\Expr $callback, Type $argType): Type
	{
		if ($callback instanceof Node\Expr\Closure || $callback instanceof Node\Expr\ArrowFunction) {
			$clone = clone $callback;
			$wrappedType = new ConstantArrayType(
				[new ConstantIntegerType(0)],
				[$argType],
				isList: TrinaryLogic::createYes(),
			);
			$clone->setAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME, [new Node\Arg(new TypeExpr($wrappedType))]);
			$clone->setAttribute('phpstanCachedTypes', []);
			$clone->setAttribute(ExprPrinter::ATTRIBUTE_CACHE_KEY, null);
			$clone->setAttribute('startFilePos', -(++self::$cloneCounter));

			$closureType = $scope->getType($clone);
			if ($closureType->isCallable()->yes()) {
				return $closureType->getCallableParametersAcceptors($scope)[0]->getReturnType();
			}
		}

		return $scope->getType(new FuncCall($callback, [
			new Node\Arg(new TypeExpr($argType)),
		]));
	}

	/**
	 * @return list<AccessoryType>
	 */
	private function getAccessoryTypes(Type $arrayType, Type $valueType): array
	{
		$accessoryTypes = [];
		foreach (TypeUtils::getAccessoryTypes($arrayType) as $accessoryType) {
			if (!$accessoryType instanceof HasOffsetValueType) {
				$accessoryTypes[] = $accessoryType;
				continue;
			}

			$accessoryTypes[] = new HasOffsetValueType($accessoryType->getOffsetType(), $valueType);
		}

		return $accessoryTypes;
	}

}
