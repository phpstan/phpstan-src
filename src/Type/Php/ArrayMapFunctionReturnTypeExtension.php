<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Parser\ArrayMapArgVisitor;
use PHPStan\Reflection\ArrayMapCallbackParameterTypes;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\UnpackedArgumentTypes;
use PHPStan\ShouldNotHappenException;
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
use function in_array;

#[AutowiredService]
final class ArrayMapFunctionReturnTypeExtension implements DynamicFunctionReturnTypeExtension
{

	/** The largest number of array lists that unpacked list shapes can pass and that are mapped one by one */
	private const UNPACKED_ARRAY_LISTS_LIMIT = 16;

	public function isFunctionSupported(FunctionReflection $functionReflection): bool
	{
		return $functionReflection->getName() === 'array_map';
	}

	public function getTypeFromFunctionCall(FunctionReflection $functionReflection, FuncCall $functionCall, Scope $scope): ?Type
	{
		$args = $functionCall->getArgs();
		if (count($args) >= 2 && in_array(true, array_map(static fn (Node\Arg $arg) => $arg->unpack, $args), true)) {
			$arrayArgs = array_slice($args, 1);
			$unpackedArgTypes = array_map(static fn (Node\Arg $arg) => $scope->getType($arg->value), $arrayArgs);
			$unpacked = array_map(static fn (Node\Arg $arg) => $arg->unpack, $arrayArgs);

			$arrayLists = self::getUnpackedArrayLists($unpackedArgTypes, $unpacked);
			if ($arrayLists !== null) {
				$requiredParametersCount = self::getRequiredParametersCount($args[0]->value);
				$types = [];
				foreach ($arrayLists as $arrayList) {
					// PHP throws an ArgumentCountError when the callback gets fewer values than it requires
					if (count($arrayList) < $requiredParametersCount) {
						continue;
					}

					$type = $this->getTypeFromArgs($scope, [$args[0], ...array_map(static fn (Type $arrayType) => new Node\Arg(new TypeExpr($arrayType)), $arrayList)], null, []);
					if ($type === null) {
						return null;
					}
					$types[] = $type;
				}

				if ($types === []) {
					return null;
				}

				return TypeCombinator::union(...$types);
			}

			[$arrayTypes, $restType] = UnpackedArgumentTypes::expand($unpackedArgTypes, $unpacked);
			if ($restType !== null) {
				return self::getTypeForUnknownArrayCount($scope, $args[0]->value, $unpackedArgTypes, $unpacked, $arrayTypes, $restType);
			}

			return $this->getTypeFromArgs($scope, [$args[0], ...array_map(static fn (Type $arrayType) => new Node\Arg(new TypeExpr($arrayType)), $arrayTypes)], $unpackedArgTypes, $unpacked);
		}

		return $this->getTypeFromArgs($scope, $args, null, []);
	}

	/**
	 * Returns each list of arrays the args can pass, when every unpacked arg is
	 * a union of list shapes, so that each list can be mapped as a known number
	 * of arrays. Returns null when the number of arrays is not known.
	 *
	 * @param list<Type> $argTypes
	 * @param list<bool> $unpacked
	 * @return list<list<Type>>|null
	 */
	private static function getUnpackedArrayLists(array $argTypes, array $unpacked): ?array
	{
		$arrayLists = [[]];
		foreach ($argTypes as $i => $argType) {
			$choices = [[$argType]];
			if ($unpacked[$i]) {
				$constantArrays = $argType->getConstantArrays();
				if ($constantArrays === []) {
					return null;
				}

				$choices = [];
				foreach ($constantArrays as $constantArray) {
					foreach ($constantArray->getAllArrays() as $array) {
						if (!self::hasPositionKeys($array)) {
							continue;
						}
						$choices[] = $array->getValueTypes();
					}
				}
				if ($choices === []) {
					return null;
				}
			}

			$nextArrayLists = [];
			foreach ($arrayLists as $arrayList) {
				foreach ($choices as $choice) {
					$nextArrayLists[] = [...$arrayList, ...$choice];
				}
			}
			if (count($nextArrayLists) > self::UNPACKED_ARRAY_LISTS_LIMIT) {
				return null;
			}
			$arrayLists = $nextArrayLists;
		}

		return $arrayLists;
	}

	/**
	 * Whether the keys are 0, 1, 2 and so on. The optional keys of a list shape
	 * also give arrays with a gap, which the list cannot be, and string keys
	 * are passed as named args.
	 */
	private static function hasPositionKeys(ConstantArrayType $array): bool
	{
		foreach ($array->getKeyTypes() as $position => $keyType) {
			if ($keyType->getValue() !== $position) {
				return false;
			}
		}

		return true;
	}

	/**
	 * The number of values a closure written in the call requires, 0 for other callbacks.
	 */
	private static function getRequiredParametersCount(Node\Expr $callback): int
	{
		if (!$callback instanceof Node\Expr\Closure && !$callback instanceof Node\Expr\ArrowFunction) {
			return 0;
		}

		$count = 0;
		foreach ($callback->params as $param) {
			if ($param->default !== null || $param->variadic) {
				break;
			}
			$count++;
		}

		return $count;
	}

	/**
	 * @param list<Node\Arg> $args
	 * @param list<Type>|null $unpackedArgTypes the arg types the arrays in $args were expanded from
	 * @param list<bool> $unpacked whether each of these args is unpacked
	 */
	private function getTypeFromArgs(Scope $scope, array $args, ?array $unpackedArgTypes, array $unpacked): ?Type
	{
		$numArgs = count($args);
		if ($numArgs < 2) {
			return null;
		}

		$singleArrayArgument = !isset($args[2]);
		$callback = $args[0]->value;
		$callableType = $scope->getType($callback);
		$callableIsNull = $callableType->isNull()->yes();

		if ($callableType->isCallable()->yes()) {
			$arrayArgs = array_slice($args, 1);
			$arrayTypes = array_map(static fn (Node\Arg $arg) => $scope->getType($arg->value), $arrayArgs);
			$callbackValueTypes = array_map(static fn (Type $arrayType) => $arrayType->getIterableValueType(), $arrayTypes);
			if ($unpackedArgTypes !== null) {
				// the args are the arrays expand() gave, so the unpacked args decide the padding
				$callbackValueTypes = ArrayMapCallbackParameterTypes::addPaddingNullToArgs($callbackValueTypes, null, $unpackedArgTypes, $unpacked)[0];
			} else {
				$callbackValueTypes = ArrayMapCallbackParameterTypes::addPaddingNull($callbackValueTypes, $arrayTypes);
			}
			$valueType = $scope->getType(new FuncCall(
				$callback,
				array_map(
					static fn (Type $valueType) => new Node\Arg(new TypeExpr($valueType)),
					$callbackValueTypes,
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

	/**
	 * The number of arrays is not known, so the callback is called with the
	 * arrays after the known ones unpacked. One array keeps its keys, more
	 * arrays give a list.
	 *
	 * @param list<Type> $argTypes the types of the args after the callback
	 * @param list<bool> $unpacked whether each of these args is unpacked
	 * @param list<Type> $arrayTypes the arrays whose position is known
	 * @param Type $restArrayType the type of the arrays after them
	 */
	private static function getTypeForUnknownArrayCount(Scope $scope, Node\Expr $callback, array $argTypes, array $unpacked, array $arrayTypes, Type $restArrayType): ?Type
	{
		$callableType = $scope->getType($callback);
		[$valueTypes, $restValueType] = ArrayMapCallbackParameterTypes::getCallbackValueTypes($argTypes, $unpacked);
		if ($restValueType === null) {
			throw new ShouldNotHappenException();
		}
		if ($callableType->isNull()->yes()) {
			// One array is returned as it is, more arrays give a list with one value per array.
			// The type system cannot say how many general arrays there are, so assume more than one.
			$tupleValueTypes = [];
			$size = null;
			$addNull = false;
			foreach ([...$arrayTypes, $restArrayType] as $arrayType) {
				$tupleValueTypes[] = $arrayType->getIterableValueType();
				$arraySizes = $arrayType->getArraySize()->getConstantScalarValues();
				if (count($arraySizes) !== 1 || ($size !== null && $size !== $arraySizes[0])) {
					$addNull = true;
				}
				$size = $arraySizes[0] ?? null;
			}
			// as for a known number of arrays, null pads each array that can be shorter
			$tupleValueType = TypeCombinator::union(...$tupleValueTypes);
			if ($addNull) {
				$tupleValueType = TypeCombinator::addNull($tupleValueType);
			}
			$tupleType = TypeCombinator::intersect(
				new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $tupleValueType),
				new AccessoryArrayListType(),
				new NonEmptyArrayType(),
			);

			return TypeCombinator::intersect(new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $tupleType), new AccessoryArrayListType());
		}
		if (!$callableType->isCallable()->yes()) {
			return null;
		}

		$callArgs = [];
		foreach ($valueTypes as $valueType) {
			$callArgs[] = new Node\Arg(new TypeExpr($valueType));
		}
		$callArgs[] = new Node\Arg(
			new TypeExpr(TypeCombinator::intersect(new ArrayType(IntegerRangeType::createAllGreaterThanOrEqualTo(0), $restValueType), new AccessoryArrayListType())),
			unpack: true,
		);
		$valueType = $scope->getType(new FuncCall($callback, $callArgs));

		$keyTypes = [IntegerRangeType::createAllGreaterThanOrEqualTo(0)];
		$isList = true;
		foreach ([...$arrayTypes, $restArrayType] as $arrayType) {
			if ($arrayType->isList()->yes()) {
				continue;
			}

			$isList = false;
			$keyTypes[] = $arrayType->getIterableKeyType();
		}

		if ($isList) {
			return TypeCombinator::intersect(new ArrayType($keyTypes[0], $valueType), new AccessoryArrayListType());
		}

		return new ArrayType(TypeCombinator::union(...$keyTypes), $valueType);
	}

	private static function resolveCallbackReturnType(Scope $scope, Node\Expr $callback, Type $argType): Type
	{
		if ($callback instanceof Node\Expr\Closure || $callback instanceof Node\Expr\ArrowFunction) {
			$clone = clone $callback;
			$wrappedType = new ConstantArrayType(
				[new ConstantIntegerType(0)],
				[$argType],
				isList: TrinaryLogic::createYes(),
			);
			// the resolved-closure-type cache is keyed by node identity
			// (ClosureTypeResolver::$cachedTypes), so the clone starts uncached
			$clone->setAttribute(ArrayMapArgVisitor::ATTRIBUTE_NAME, [new Node\Arg(new TypeExpr($wrappedType))]);

			return $scope->getType($clone)->getCallableParametersAcceptors($scope)[0]->getReturnType();
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
