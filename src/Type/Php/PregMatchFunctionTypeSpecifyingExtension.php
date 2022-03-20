<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifier;
use PHPStan\Analyser\TypeSpecifierAwareExtension;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BitwiseFlagHelper;
use PHPStan\Type\Constant\ConstantArrayTypeBuilder;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\FunctionTypeSpecifyingExtension;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\StringType;
use PHPStan\Type\TypeCombinator;
use function strtolower;

final class PregMatchFunctionTypeSpecifyingExtension implements FunctionTypeSpecifyingExtension, TypeSpecifierAwareExtension
{

	public function __construct(
		private BitwiseFlagHelper $bitwiseFlagAnalyser,
	)
	{
	}

	private TypeSpecifier $typeSpecifier;

	public function isFunctionSupported(
		FunctionReflection $functionReflection,
		FuncCall $node,
		TypeSpecifierContext $context,
	): bool
	{
		return strtolower($functionReflection->getName()) === 'preg_match'
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
		$matchesArg = $args[2]->value ?? null;
		$optionsExpr = $args[3]->value ?? null;

		if ($matchesArg === null) {
			return new SpecifiedTypes();
		}

		if ($context->false()) {
			return $this->typeSpecifier->create(
				$matchesArg,
				new ArrayType(new MixedType(), new MixedType()),
				$context->negate(),
				false,
				$scope,
			);
		}

		$valueType = new StringType();
		if ($optionsExpr !== null) {
			$unmatchedAsNull = $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($optionsExpr, $scope, 'PREG_UNMATCHED_AS_NULL')->yes();
			$offsetCapture = $this->bitwiseFlagAnalyser->bitwiseOrContainsConstant($optionsExpr, $scope, 'PREG_OFFSET_CAPTURE')->yes();
			$weDontKnowFlags = !$unmatchedAsNull && !$offsetCapture;

			$optionsType = $scope->getType($optionsExpr);
			if ($optionsType instanceof ConstantIntegerType) {
				$weDontKnowFlags = false;
			}

			if ($weDontKnowFlags || $offsetCapture) {
				$builder = ConstantArrayTypeBuilder::createEmpty();
				if ($weDontKnowFlags || $unmatchedAsNull) {
					$builder->setOffsetValueType(new ConstantIntegerType(0), TypeCombinator::addNull(new StringType()));
				} else {
					$builder->setOffsetValueType(new ConstantIntegerType(0), new StringType());
				}
				$builder->setOffsetValueType(new ConstantIntegerType(1), IntegerRangeType::fromInterval(-1, null));
				$valueType = $builder->getArray();

				if ($weDontKnowFlags) {
					$valueType = TypeCombinator::union(TypeCombinator::addNull(new StringType()), $valueType);
				}

			} elseif ($unmatchedAsNull) {
				$valueType = TypeCombinator::addNull(new StringType());
			}
		}

		$arrayType = new ArrayType(
			TypeCombinator::union(new StringType(), IntegerRangeType::fromInterval(0, null)),
			$valueType,
		);

		return $this->typeSpecifier->create(
			$matchesArg,
			$arrayType,
			$context,
			false,
			$scope,
		);
	}

	public function setTypeSpecifier(TypeSpecifier $typeSpecifier): void
	{
		$this->typeSpecifier = $typeSpecifier;
	}

}
