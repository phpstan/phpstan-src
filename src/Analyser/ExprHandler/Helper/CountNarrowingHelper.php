<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\FuncCall;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\ShouldNotHappenException;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Accessory\HasOffsetValueType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function count;
use function sprintf;
use const COUNT_NORMAL;

/**
 * Narrows a count()/sizeof() argument by the known size of the result -
 * shape reconstruction for constant arrays and lists. The former
 * TypeSpecifier::specifyTypesForCountFuncCall(), composed through
 * createForSubject() instead of TypeSpecifier::create().
 */
#[AutowiredService]
final class CountNarrowingHelper
{

	public function __construct(
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private ExprPrinter $exprPrinter,
	)
	{
	}

	public function isNormalCountCall(FuncCall $countFuncCall, Type $typeToCount, MutatingScope $scope): TrinaryLogic
	{
		if (count($countFuncCall->getArgs()) === 1) {
			return TrinaryLogic::createYes();
		}

		$modeArg = $countFuncCall->getArgs()[1]->value;
		// the mode argument was processed with the call - a census over the suite
		// and self-analysis found every ask answered from the stored result
		$storage = $scope->getCurrentExpressionResultStorage();
		$modeResult = $storage !== null ? $storage->findExpressionResult($modeArg) : null;
		if ($modeResult === null) {
			throw new ShouldNotHappenException(sprintf('count() mode argument on line %d has no stored ExpressionResult.', $modeArg->getStartLine()));
		}
		$mode = $modeResult->getTypeOnScope($scope, $scope->nativeTypesPromoted);

		return (new ConstantIntegerType(COUNT_NORMAL))->isSuperTypeOf($mode)->result->or($typeToCount->getIterableValueType()->isArray()->negate());
	}

	public function specifyCountSize(
		FuncCall $countFuncCall,
		Type $type,
		Type $sizeType,
		TypeSpecifierContext $context,
		MutatingScope $scope,
		Expr $rootExpr,
	): ?SpecifiedTypes
	{
		$isConstantArray = $type->isConstantArray();
		$isList = $type->isList();
		$oneOrMore = IntegerRangeType::fromInterval(1, null);
		if (
			!$this->isNormalCountCall($countFuncCall, $type, $scope)->yes()
			|| (!$isConstantArray->yes() && !$isList->yes())
			|| !$oneOrMore->isSuperTypeOf($sizeType)->yes()
			|| $sizeType->isSuperTypeOf($type->getArraySize())->yes()
		) {
			return null;
		}

		if ($context->falsey() && $isConstantArray->yes()) {
			$remainingSize = TypeCombinator::remove($type->getArraySize(), $sizeType);
			if (!$remainingSize instanceof NeverType) {
				$negatedContext = $context->false()
					? TypeSpecifierContext::createTrue()
					: TypeSpecifierContext::createTruthy();
				$result = $this->specifyCountSize(
					$countFuncCall,
					$type,
					$remainingSize,
					$negatedContext,
					$scope,
					$rootExpr,
				);
				if ($result !== null) {
					return $result;
				}
			}

			// Fallback: directly filter constant arrays by their exact sizes.
			// This avoids using TypeCombinator::remove() with falsey context,
			// which can incorrectly remove arrays whose count doesn't match
			// but whose shape is a subtype of the matched array.
			$keptTypes = [];
			foreach ($type->getConstantArrays() as $arrayType) {
				if ($sizeType->isSuperTypeOf($arrayType->getArraySize())->yes()) {
					continue;
				}

				$keptTypes[] = $arrayType;
			}
			if ($keptTypes !== []) {
				return $this->defaultNarrowingHelper->createForSubject(
					$countFuncCall->getArgs()[0]->value,
					TypeCombinator::union(...$keptTypes),
					$context->negate(),
					$scope,
				)->setRootExpr($rootExpr);
			}
		}

		$resultTypes = [];
		foreach ($type->getArrays() as $arrayType) {
			$isSizeSuperTypeOfArraySize = $sizeType->isSuperTypeOf($arrayType->getArraySize());
			if ($isSizeSuperTypeOfArraySize->no()) {
				continue;
			}

			if ($context->falsey() && $isSizeSuperTypeOfArraySize->maybe()) {
				continue;
			}

			$resultTypes[] = $isList->yes()
				? $arrayType->truncateListToSize($sizeType)
				: TypeCombinator::intersect($arrayType, new NonEmptyArrayType());
		}

		if ($context->truthy() && $isConstantArray->yes() && $isList->yes()) {
			$hasOptionalKeysOrUnsealed = false;
			foreach ($type->getConstantArrays() as $arrayType) {
				if ($arrayType->getOptionalKeys() !== [] || $arrayType->isUnsealed()->yes()) {
					// Unsealed CATs can't be narrowed via the
					// `HasOffsetValueType`-only shortcut below — the
					// intersection of an unsealed shape with a single-slot
					// constraint produces `NeverType`. Fall through to
					// the full builder-based narrowing, which carries the
					// unsealed slot via the loop above.
					$hasOptionalKeysOrUnsealed = true;
					break;
				}
			}

			if (!$hasOptionalKeysOrUnsealed) {
				$argExpr = $countFuncCall->getArgs()[0]->value;
				$argExprString = $this->exprPrinter->printExpr($argExpr);

				$sizeMin = null;
				$sizeMax = null;
				if ($sizeType instanceof ConstantIntegerType) {
					$sizeMin = $sizeType->getValue();
					$sizeMax = $sizeType->getValue();
				} elseif ($sizeType instanceof IntegerRangeType) {
					$sizeMin = $sizeType->getMin();
					$sizeMax = $sizeType->getMax();
				}

				$sureTypes = [];
				$sureNotTypes = [];

				if ($sizeMin !== null && $sizeMin >= 1) {
					$sureTypes[$argExprString] = [$argExpr, new HasOffsetValueType(new ConstantIntegerType($sizeMin - 1), new MixedType())];
				}
				if ($sizeMax !== null) {
					$sureNotTypes[$argExprString] = [$argExpr, new HasOffsetValueType(new ConstantIntegerType($sizeMax), new MixedType())];
				}

				if ($sureTypes !== [] || $sureNotTypes !== []) {
					return (new SpecifiedTypes($sureTypes, $sureNotTypes))->setRootExpr($rootExpr);
				}
			}
		}

		return $this->defaultNarrowingHelper->createForSubject($countFuncCall->getArgs()[0]->value, TypeCombinator::union(...$resultTypes), $context, $scope)->setRootExpr($rootExpr);
	}

}
