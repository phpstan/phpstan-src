<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Php\PhpVersion;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

#[AutowiredService]
final class ArrayKeyExistsFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

	public function __construct(
		private PhpVersion $phpVersion,
	)
	{
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return in_array($functionReflection->getName(), ['array_key_exists', 'key_exists'], true)
			&& !$context->null();
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
		$key = $args[0]->value;
		$array = $args[1]->value;
		$keyType = $scope->getType($key)->toArrayKey();
		$arrayType = $scope->getType($array);

		if (
			!$keyType instanceof ConstantIntegerType
			&& !$keyType instanceof ConstantStringType
		) {
			if ($context->true()) {
				$specifiedTypes = new SpecifiedTypes();

				$nonEmptyType = $arrayType->isArray()->yes()
					? new NonEmptyArrayType()
					: TypeCombinator::intersect(new ArrayType(new MixedType(), new MixedType()), new NonEmptyArrayType());
				$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
					$array,
					$nonEmptyType,
					$context,
					$scope,
				));

				if ($arrayType->isIterableAtLeastOnce()->no()) {
					return $specifiedTypes;
				}

				$arrayKeyType = $arrayType->getIterableKeyType();
				if ($keyType->isString()->yes()) {
					$arrayKeyType = $arrayKeyType->toString();
				} elseif ($keyType->isString()->maybe()) {
					$arrayKeyType = TypeCombinator::union($arrayKeyType, $arrayKeyType->toString());
				}

				$specifiedTypes = $specifiedTypes->unionWith($this->typeSpecifier->create(
					$key,
					$arrayKeyType,
					$context,
					$scope,
				));

				$arrayDimFetch = new ArrayDimFetch(
					$array,
					$key,
				);

				return $specifiedTypes->unionWith($this->typeSpecifier->create(
					$arrayDimFetch,
					$arrayType->getIterableValueType(),
					$context,
					$scope,
				))->setEquality();
			}

			return new SpecifiedTypes();
		}

		if ($context->true()) {
			$type = new IntersectionType([
				new ArrayType(new MixedType(), new MixedType()),
				new HasOffsetType($keyType),
			]);
		} elseif ($this->phpVersion->throwsValueErrorForInternalFunctions()) {
			$specifiedTypes = $this->typeSpecifier->create(
				$array,
				new HasOffsetType($keyType),
				$context,
				$scope,
			);

			$type = new ArrayType(new MixedType(), new MixedType());
			$type = $type->unsetOffset($keyType);
			return $specifiedTypes->unionWith($this->typeSpecifier->create(
				$array,
				$type,
				$context->negate(),
				$scope,
			));
		} else {
			$type = new HasOffsetType($keyType);
		}

		return $this->typeSpecifier->create(
			$array,
			$type,
			$context,
			$scope,
		);
	}

}
