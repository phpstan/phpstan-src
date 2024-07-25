<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\MixedType;
use PHPStan\Type\TypeCombinator;
use function count;
use function in_array;

final class ArrayKeyExistsFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	private TypeSpecifier $typeSpecifier;

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
		if (count($node->getArgs()) < 2) {
			return new SpecifiedTypes();
		}
		$key = $node->getArgs()[0]->value;
		$array = $node->getArgs()[1]->value;
		$keyType = $scope->getType($key);
		$arrayType = $scope->getType($array);

		if (!$keyType instanceof ConstantIntegerType
			&& !$keyType instanceof ConstantStringType
			&& !$arrayType->isIterableAtLeastOnce()->no()) {
			if ($context->true()) {
				$arrayKeyType = $arrayType->getIterableKeyType();
				if ($keyType->isString()->yes()) {
					$arrayKeyType = $arrayKeyType->toString();
				} elseif ($keyType->isString()->maybe()) {
					$arrayKeyType = TypeCombinator::union($arrayKeyType, $arrayKeyType->toString());
				}

				$specifiedTypes = $this->typeSpecifier->create(
					$key,
					$arrayKeyType,
					$context,
					false,
					$scope,
				);

				$arrayDimFetch = new ArrayDimFetch(
					$array,
					$key,
				);

				return $specifiedTypes->unionWith($this->typeSpecifier->create(
					$arrayDimFetch,
					$arrayType->getIterableValueType(),
					$context,
					false,
					$scope,
					new Identical($arrayDimFetch, new ConstFetch(new Name('__PHPSTAN_FAUX_CONSTANT'))),
				));
			}

			return new SpecifiedTypes();
		}

		if ($context->true()) {
			$type = TypeCombinator::intersect(
				new ArrayType(new MixedType(), new MixedType()),
				new HasOffsetType($keyType),
			);
		} else {
			$type = new HasOffsetType($keyType);
		}

		return $this->typeSpecifier->create(
			$array,
			$type,
			$context,
			false,
			$scope,
		);
	}

}
