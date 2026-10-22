<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node\Arg;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\Array_;
use PhpParser\Node\Expr\BinaryOp\BooleanOr;
use PhpParser\Node\Expr\BinaryOp\Identical;
use PhpParser\Node\Expr\FuncCall;
use PhpParser\Node\Name\FullyQualified;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Reflection\ParametersAcceptorSelector;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Turbo\ImplementedInTurboExtension;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Type;

/**
 * The scope after a foreach whose body may not have run narrows its iteratee
 * to what an unentered loop leaves: `$iteratee === [] || is_object($iteratee)`.
 */
#[AutowiredService]
#[ImplementedInTurboExtension(implementation: __DIR__ . '/../../../../turbo-ext/src/ForeachEmptyOrObjectNarrowingHelper.cpp')]
final class ForeachEmptyOrObjectNarrowingHelper
{

	public function __construct(
		private ReflectionProvider $reflectionProvider,
		private IdenticalNarrowingHelper $identicalNarrowingHelper,
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private BooleanNarrowingHelper $booleanNarrowingHelper,
	)
	{
	}

	/**
	 * The truthy narrowing of the disjunction, composed the way its walked
	 * synthetic would compute it: the comparison from the iteratee's result,
	 * is_object() from its conditional return type. The walk is the
	 * composition's miss seam.
	 */
	public function narrowToEmptyOrObject(NodeScopeResolver $nodeScopeResolver, MutatingScope $scope, Expr $iteratee, ExpressionResult $iterateeResult, ExpressionResultStorage $storage): MutatingScope
	{
		$identical = new Identical($iteratee, new Array_([]));
		$isObjectCall = new FuncCall(new FullyQualified('is_object'), [new Arg($iteratee)]);
		$condition = new BooleanOr($identical, $isObjectCall);

		return $this->composeScope($nodeScopeResolver, $scope, $condition, $identical, $isObjectCall, $iterateeResult, $storage)
			?? $nodeScopeResolver->narrowScopeWithCondition($scope, $condition, TypeSpecifierContext::createTruthy());
	}

	private function composeScope(
		NodeScopeResolver $nodeScopeResolver,
		MutatingScope $scope,
		BooleanOr $condition,
		Identical $identical,
		FuncCall $isObjectCall,
		ExpressionResult $iterateeResult,
		ExpressionResultStorage $storage,
	): ?MutatingScope
	{
		$isObjectName = new FullyQualified('is_object');
		if (!$this->reflectionProvider->hasFunction($isObjectName, $scope)) {
			return null;
		}
		$isObjectFunction = $this->reflectionProvider->getFunction($isObjectName, $scope);
		$identicalNarrowingHelper = $this->identicalNarrowingHelper;
		$defaultNarrowingHelper = $this->defaultNarrowingHelper;
		$emptyArrayType = new ConstantArrayType([], []);
		$subjectArgResult = $identicalNarrowingHelper->captureFirstArgResult($identical->left, $storage);

		$leftTypes = static fn (MutatingScope $s, TypeSpecifierContext $context): ?SpecifiedTypes => $identicalNarrowingHelper->specifyIdenticalAgainstType(
			$identical->left,
			$iterateeResult,
			$identical->right,
			$emptyArrayType,
			$context,
			$s,
			$subjectArgResult,
			static fn (): Type => self::emptyArrayComparison($iterateeResult->getType(), $emptyArrayType),
		);
		$rightTypes = static function (MutatingScope $s, TypeSpecifierContext $context) use ($defaultNarrowingHelper, $isObjectCall, $isObjectFunction): ?SpecifiedTypes {
			$acceptor = ParametersAcceptorSelector::selectFromArgs($s, $isObjectCall->getArgs(), $isObjectFunction->getVariants(), $isObjectFunction->getNamedArgumentsVariants());

			return $defaultNarrowingHelper->specifyTypesFromConditionalReturnType($context, $isObjectCall, $acceptor, $s);
		};

		$leftTruthyTypes = $leftTypes($scope, TypeSpecifierContext::createTruthy());
		$leftFalseyTypes = $leftTypes($scope, TypeSpecifierContext::createFalsey());
		if ($leftTruthyTypes === null || $leftFalseyTypes === null) {
			return null;
		}
		$leftFalseyScope = $scope->applySpecifiedTypes($leftFalseyTypes->setRootExpr($identical));
		$rightTruthyTypes = $rightTypes($leftFalseyScope, TypeSpecifierContext::createTruthy());
		if ($rightTruthyTypes === null) {
			return null;
		}
		$leftTruthyScope = $scope->applySpecifiedTypes($leftTruthyTypes->setRootExpr($identical));
		$rightTruthyScope = $leftFalseyScope->applySpecifiedTypes($rightTruthyTypes->setRootExpr($isObjectCall));

		$types = $this->booleanNarrowingHelper->specifyDisjunction(
			$nodeScopeResolver,
			$scope,
			TypeSpecifierContext::createTruthy(),
			$condition,
			$identical,
			static fn (MutatingScope $s, TypeSpecifierContext $context): SpecifiedTypes => $leftTypes($s, $context) ?? new SpecifiedTypes(),
			static fn (bool $nativeTypesPromoted): Type => self::emptyArrayComparison($nativeTypesPromoted ? $iterateeResult->getNativeType() : $iterateeResult->getType(), $emptyArrayType),
			static fn (): MutatingScope => $leftTruthyScope,
			static fn (): MutatingScope => $leftFalseyScope,
			$isObjectCall,
			static fn (MutatingScope $s, TypeSpecifierContext $context): SpecifiedTypes => $rightTypes($s, $context) ?? new SpecifiedTypes(),
			static fn (bool $nativeTypesPromoted): Type => self::isObjectVerdict($nativeTypesPromoted ? $iterateeResult->getNativeType() : $iterateeResult->getType()),
			static fn (): MutatingScope => $rightTruthyScope,
		);

		return $scope->applySpecifiedTypes($types);
	}

	/** The type of `$iteratee === []`. */
	private static function emptyArrayComparison(Type $iterateeType, ConstantArrayType $emptyArrayType): Type
	{
		if ($iterateeType->equals($emptyArrayType)) {
			return new ConstantBooleanType(true);
		}
		if ($emptyArrayType->isSuperTypeOf($iterateeType)->no()) {
			return new ConstantBooleanType(false);
		}

		return new BooleanType();
	}

	/** The type of `is_object($iteratee)`. */
	private static function isObjectVerdict(Type $iterateeType): Type
	{
		$isObject = $iterateeType->isObject();
		if ($isObject->yes()) {
			return new ConstantBooleanType(true);
		}
		if ($isObject->no()) {
			return new ConstantBooleanType(false);
		}

		return new BooleanType();
	}

}
