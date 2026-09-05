<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Countable;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\Scalar;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\RicherScopeGetTypeHelper;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\AlwaysRememberedExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Type\Accessory\AccessoryLowercaseStringType;
use PHPStan\Type\Accessory\AccessoryNonEmptyStringType;
use PHPStan\Type\Accessory\AccessoryNonFalsyStringType;
use PHPStan\Type\Accessory\AccessoryUppercaseStringType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ArrayType;
use PHPStan\Type\BooleanType;
use PHPStan\Type\Constant\ConstantArrayType;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantFloatType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\FloatType;
use PHPStan\Type\Generic\GenericClassStringType;
use PHPStan\Type\IntegerRangeType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\ResourceType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\UnionType;
use function count;
use function in_array;
use function is_string;

/**
 * New-world narrowing for `===` (and, via a negated context, `!==`): composed
 * from the operands' ExpressionResults, no scope asks and no synthetic nodes.
 * The evaluation scope is a create-time constant of the calling handler (it
 * carries the flavour and feeds entry composition), never the asking scope.
 *
 * Null from the entry points means the shape carries no specific narrowing
 * (unknown-class ::class sides, null-context asks) and the caller applies
 * the default truthy/falsey narrowing.
 */
#[AutowiredService]
final class IdenticalNarrowingHelper
{

	public function __construct(
		private DefaultNarrowingHelper $defaultNarrowingHelper,
		private ReflectionProvider $reflectionProvider,
		private CountNarrowingHelper $countNarrowingHelper,
		private ExprPrinter $exprPrinter,
		private RicherScopeGetTypeHelper $richerScopeGetTypeHelper,
	)
	{
	}

	/**
	 * @param callable(): Type $identicalTypeCallback the comparison's own type
	 *        in Identical semantics (the caller flips a NotIdentical verdict)
	 */
	public function specifyIdentical(
		NodeScopeResolver $nodeScopeResolver,
		Expr $left,
		Expr $right,
		ExpressionResult $leftResult,
		ExpressionResult $rightResult,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
		?ExpressionResult $leftArgResult,
		?ExpressionResult $rightArgResult,
		callable $identicalTypeCallback,
	): ?SpecifiedTypes
	{
		if ($context->null()) {
			return null;
		}

		// slices 1+2 cover comparisons against a null/true/false literal;
		// everything else falls through to the scalar-literal slice below
		if ($left instanceof Expr\ConstFetch && in_array($left->name->toLowerString(), ['null', 'true', 'false'], true)) {
			$constantName = $left->name->toLowerString();
			$subject = $right;
			$subjectResult = $rightResult;
		} elseif ($right instanceof Expr\ConstFetch && in_array($right->name->toLowerString(), ['null', 'true', 'false'], true)) {
			$constantName = $right->name->toLowerString();
			$subject = $left;
			$subjectResult = $leftResult;
		} else {
			// a side whose TYPE is a constant bool (match (true) arms, bool
			// class constants) compares like the literal - the old
			// constant-binary handling, composed
			$unwrappedLeft = $left instanceof AlwaysRememberedExpr ? $left->getExpr() : $left;
			$unwrappedRight = $right instanceof AlwaysRememberedExpr ? $right->getExpr() : $right;
			$leftType = $this->literalType($unwrappedLeft) ?? $leftResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
			if (($leftType->isTrue()->yes() || $leftType->isFalse()->yes()) && !$unwrappedRight instanceof Expr\ConstFetch) {
				return $this->specifyAgainstBool($right, $rightResult, $leftType->isTrue()->yes(), $context, $evaluationScope);
			}
			$rightType = $this->literalType($unwrappedRight) ?? $rightResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
			if (($rightType->isTrue()->yes() || $rightType->isFalse()->yes()) && !$unwrappedLeft instanceof Expr\ConstFetch) {
				return $this->specifyAgainstBool($left, $leftResult, $rightType->isTrue()->yes(), $context, $evaluationScope);
			}

			$types = $this->specifyAgainstScalarLiteral($left, $right, $leftResult, $rightResult, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
			if ($types !== null) {
				return $types;
			}

			return $this->specifyGeneral($nodeScopeResolver, $left, $right, $leftResult, $rightResult, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
		}

		if ($constantName === 'null') {
			// deliberately NOT guarded by specifyDecidedComparison(): a decided
			// null comparison still emits its subtraction entry so assign-time
			// conditional holders fire ($id = $x?->prop; if ($id !== null) makes
			// $x non-null even when $id's own type already excludes null) - the
			// old path's blanket guard here is what kept bug-10482 red
			return $this->defaultNarrowingHelper->createSubjectTypes(
				$evaluationScope,
				$subject,
				$subjectResult,
				new NullType(),
				$context,
			);
		}

		return $this->specifyAgainstBool($subject, $subjectResult, $constantName === 'true', $context, $evaluationScope);
	}

	/**
	 * A bool constant pins itself through the entries and runs the subject's
	 * own narrowing in the matching bool context - identity, not truthiness:
	 * `=== false` is the false context, not falsey.
	 */
	private function specifyAgainstBool(
		Expr $subject,
		ExpressionResult $subjectResult,
		bool $value,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
	): SpecifiedTypes
	{
		$types = $this->defaultNarrowingHelper->createSubjectTypes(
			$evaluationScope,
			$subject,
			$subjectResult,
			new ConstantBooleanType($value),
			$context,
		);

		// a nullsafe chain that did not produce the constant may have
		// short-circuited instead - its own narrowing only holds when the
		// comparison succeeded
		$unwrappedSubject = $subject instanceof AlwaysRememberedExpr ? $subject->getExpr() : $subject;
		if (!$context->true() && ($unwrappedSubject instanceof Expr\NullsafeMethodCall || $unwrappedSubject instanceof Expr\NullsafePropertyFetch)) {
			return $types;
		}

		$boolContext = $value ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();

		return $types->unionWith($subjectResult->getSpecifiedTypesForScope(
			$evaluationScope,
			$context->true() ? $boolContext : $boolContext->negate(),
		));
	}

	/**
	 * Slice 3: comparisons against a scalar literal or a class constant
	 * (`$a === 5`, `$s === Foo::BAR`, `$suit === Suit::Hearts`) pin the
	 * single-valued side onto the other operand - the composed form of the
	 * finite-types narrowing at the tail of the old identical path.
	 */
	/**
	 * @param callable(): Type $identicalTypeCallback
	 */
	private function specifyAgainstScalarLiteral(
		Expr $left,
		Expr $right,
		ExpressionResult $leftResult,
		ExpressionResult $rightResult,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
		?ExpressionResult $leftArgResult,
		?ExpressionResult $rightArgResult,
		callable $identicalTypeCallback,
	): ?SpecifiedTypes
	{
		if ($this->isScalarLiteral($left)) {
			$constantExpr = $left;
			$constantResult = $leftResult;
			$subject = $right;
			$subjectResult = $rightResult;
		} elseif ($this->isScalarLiteral($right)) {
			$constantExpr = $right;
			$constantResult = $rightResult;
			$subject = $left;
			$subjectResult = $leftResult;
		} else {
			return null;
		}

		$unwrappedSubject = $subject instanceof AlwaysRememberedExpr ? $subject->getExpr() : $subject;
		if ($unwrappedSubject instanceof Expr\FuncCall) {
			$familyTypes = $this->specifyFuncCallFamilies($subject, $subjectResult, $unwrappedSubject, $constantExpr, $this->literalType($constantExpr) ?? $constantResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted), $context, $evaluationScope, $subject === $left ? $leftArgResult : $rightArgResult);
			if ($familyTypes === null) {
				return null;
			}
			if ($familyTypes !== false) {
				return $familyTypes;
			}
		} elseif ($unwrappedSubject instanceof Expr\ClassConstFetch && $unwrappedSubject->class instanceof Expr) {
			// only ::class composes; a constant fetched off an object falls back
			if ($unwrappedSubject->name instanceof Expr || $unwrappedSubject->name->toLowerString() !== 'class') {
				return null;
			}
		} elseif (!$this->isSubjectCoveredAgainstConstant($subject)) {
			return null;
		}

		$constantType = $this->literalType($constantExpr) ?? $constantResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
		if (count($constantType->getFiniteTypes()) !== 1) {
			// a class constant does not have to be single-valued
			return null;
		}

		// a provenance-recorded subject variable narrows through its defining
		// call's families, exactly like the direct call comparison - the
		// variable itself still pins to the constant alongside
		$provenanceTypes = $this->specifyThroughResultProvenance($subject, $constantExpr, $constantType, $context, $evaluationScope);
		if ($provenanceTypes !== null) {
			return $provenanceTypes->unionWith($this->defaultNarrowingHelper->createSubjectTypes(
				$evaluationScope,
				$subject,
				$subjectResult,
				$constantType,
				$context,
			));
		}

		// $a::class === Foo::class narrows $a to a final Foo when true;
		// other contexts and plain-string sides only pin the fetch
		if (
			$unwrappedSubject instanceof Expr\ClassConstFetch
			&& $unwrappedSubject->class instanceof Expr
			&& $context->true()
			&& $constantExpr instanceof Expr\ClassConstFetch
		) {
			$constantStrings = $constantType->getConstantStrings();
			if (count($constantStrings) === 1 && $constantStrings[0]->getValue() !== '') {
				if (!$this->reflectionProvider->hasClass($constantStrings[0]->getValue())) {
					// an unknown class name narrows like instanceof - not composed yet
					return null;
				}

				return $this->defaultNarrowingHelper->createForSubject(
					$unwrappedSubject->class,
					new ObjectType($constantStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal()),
					$context,
					$evaluationScope,
				)->unionWith($this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context));
			}
		}

		$decidedTypes = $this->specifyDecidedComparison($left, $right, $leftResult, $rightResult, $context, $evaluationScope, $identicalTypeCallback);
		if ($decidedTypes !== null) {
			return $decidedTypes;
		}

		$types = $this->defaultNarrowingHelper->createSubjectTypes(
			$evaluationScope,
			$subject,
			$subjectResult,
			$constantType,
			$context,
		);

		// a single-valued subject pins its value onto the literal side too
		$subjectType = $subjectResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
		if (count($subjectType->getFiniteTypes()) === 1) {
			$types = $types->unionWith($this->defaultNarrowingHelper->createSubjectTypes(
				$evaluationScope,
				$constantExpr,
				$constantResult,
				$subjectType,
				$context,
			));
		}

		return $types;
	}

	/**
	 * A statically decided comparison tells the false context nothing: the
	 * branch is dead on the certain flavour, and subtracting the constant
	 * would wrongly leak into the wider native flavour (mixed~'ab'). The
	 * NeverType entries mirror the old identical tail's no-op.
	 *
	 * @param callable(): Type $identicalTypeCallback
	 */
	private function specifyDecidedComparison(
		Expr $left,
		Expr $right,
		ExpressionResult $leftResult,
		ExpressionResult $rightResult,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
		callable $identicalTypeCallback,
	): ?SpecifiedTypes
	{
		if (!$context->false()) {
			return null;
		}

		$identicalType = $identicalTypeCallback();
		$isTrue = $identicalType->isTrue()->yes();
		if (!$isTrue && !$identicalType->isFalse()->yes()) {
			return null;
		}

		$never = new NeverType();
		$contextForTypes = $isTrue ? $context->negate() : $context;

		return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $left, $leftResult, $never, $contextForTypes)->unionWith(
			$this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $right, $rightResult, $never, $contextForTypes),
		);
	}

	private function getTypeFromGettypeStringValue(string $value): ?Type
	{
		if ($value === 'string') {
			return new StringType();
		}
		if ($value === 'array') {
			return new ArrayType(new MixedType(), new MixedType());
		}
		if ($value === 'boolean') {
			return new BooleanType();
		}
		if (in_array($value, ['resource', 'resource (closed)'], true)) {
			return new ResourceType();
		}
		if ($value === 'integer') {
			return new IntegerType();
		}
		if ($value === 'double') {
			return new FloatType();
		}
		if ($value === 'NULL') {
			return new NullType();
		}
		if ($value === 'object') {
			return new ObjectWithoutClassType();
		}

		return null;
	}

	/**
	 * The general expr-vs-expr tail of the identical narrowing: a
	 * single-valued side pins its value onto the other, otherwise both sides
	 * pin each other's types in the true context and cross-exclude in the
	 * false one. Runs only for operand shapes whose specialized narrowing is
	 * already composed - calls and ::class fetches still fall back.
	 *
	 * @param callable(): Type $identicalTypeCallback
	 */
	private function specifyGeneral(
		NodeScopeResolver $nodeScopeResolver,
		Expr $left,
		Expr $right,
		ExpressionResult $leftResult,
		ExpressionResult $rightResult,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
		?ExpressionResult $leftArgResult,
		?ExpressionResult $rightArgResult,
		callable $identicalTypeCallback,
	): ?SpecifiedTypes
	{
		$unwrappedLeft = $left instanceof AlwaysRememberedExpr ? $left->getExpr() : $left;
		$unwrappedRight = $right instanceof AlwaysRememberedExpr ? $right->getExpr() : $right;

		// a `$a::class` side falls back only where the old instanceof-style
		// blocks would fire: a true context with a single class-name string
		// on the other side; everything else narrows generically
		if ($context->true()) {
			foreach ([
				[$unwrappedLeft, $left, $leftResult, $rightResult],
				[$unwrappedRight, $right, $rightResult, $leftResult],
			] as [$sideUnwrapped, $side, $sideResult, $otherResult]) {
				if (!($sideUnwrapped instanceof Expr\ClassConstFetch) || !($sideUnwrapped->class instanceof Expr)) {
					continue;
				}
				// only `$expr::class` names the fetched-on class - any other
				// constant ($obj::TYPE === '<some class name>') compares plain
				// values and must not narrow the fetched-on object
				if ($sideUnwrapped->name instanceof Expr || $sideUnwrapped->name->toLowerString() !== 'class') {
					continue;
				}
				$otherStrings = $otherResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted)->getConstantStrings();
				if (count($otherStrings) !== 1 || $otherStrings[0]->getValue() === '') {
					continue;
				}
				if (!$this->reflectionProvider->hasClass($otherStrings[0]->getValue())) {
					// an unknown class narrows like instanceof: intersect the
					// fetched-on object with the named type (it cannot be pinned
					// as final without reflection)
					return $this->defaultNarrowingHelper->createForSubject(
						$sideUnwrapped->class,
						new ObjectType($otherStrings[0]->getValue()),
						$context,
						$evaluationScope,
					)->unionWith($this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $side, $sideResult, $otherStrings[0], $context));
				}

				return $this->defaultNarrowingHelper->createForSubject(
					$sideUnwrapped->class,
					new ObjectType($otherStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($otherStrings[0]->getValue())->asFinal()),
					$context,
					$evaluationScope,
				)->unionWith($this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $side, $sideResult, $otherStrings[0], $context));
			}
		}

		$leftType = $this->literalType($unwrappedLeft) ?? $leftResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
		$rightType = $this->literalType($unwrappedRight) ?? $rightResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);

		// fn1() === fn2() merges both normalized directions
		if ($unwrappedLeft instanceof Expr\FuncCall && $unwrappedRight instanceof Expr\FuncCall) {
			// count($a) === count($b): a decided size flows across; otherwise
			// one non-empty side makes both non-empty
			if (
				$context->true()
				&& $unwrappedLeft->name instanceof Name && in_array($unwrappedLeft->name->toLowerString(), ['count', 'sizeof'], true) && !$unwrappedLeft->isFirstClassCallable() && isset($unwrappedLeft->getArgs()[0])
				&& $unwrappedRight->name instanceof Name && in_array($unwrappedRight->name->toLowerString(), ['count', 'sizeof'], true) && !$unwrappedRight->isFirstClassCallable() && isset($unwrappedRight->getArgs()[0])
			) {
				if ($leftArgResult === null || $rightArgResult === null) {
					return null;
				}
				$rightArgType = $rightArgResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
				$countTypes = $this->countNarrowingHelper->specifyCountSize($unwrappedRight, $rightArgType, $leftType, $context, $evaluationScope, $unwrappedRight);
				if ($countTypes !== null) {
					return $countTypes;
				}

				$leftArgType = $leftArgResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
				if (
					$leftArgType->isArray()->yes()
					&& $rightArgType->isArray()->yes()
					&& !$rightType->isConstantScalarValue()->yes()
					&& ($leftArgType->isIterableAtLeastOnce()->yes() || $rightArgType->isIterableAtLeastOnce()->yes())
				) {
					return $this->defaultNarrowingHelper->createForSubject($unwrappedLeft->getArgs()[0]->value, new NonEmptyArrayType(), $context, $evaluationScope)->unionWith(
						$this->defaultNarrowingHelper->createForSubject($unwrappedRight->getArgs()[0]->value, new NonEmptyArrayType(), $context, $evaluationScope),
					);
				}
			}

			$leftDirection = $this->specifyFuncCallFamilies($left, $leftResult, $unwrappedLeft, $right, $rightType, $context, $evaluationScope, $leftArgResult);
			$rightDirection = $this->specifyFuncCallFamilies($right, $rightResult, $unwrappedRight, $left, $leftType, $context, $evaluationScope, $rightArgResult);
			if ($leftDirection === null || $rightDirection === null) {
				return null;
			}
			$merged = null;
			if ($leftDirection !== false) {
				$merged = $leftDirection;
			}
			if ($rightDirection !== false) {
				$merged = $merged !== null ? $merged->unionWith($rightDirection) : $rightDirection;
			}
			if ($merged !== null) {
				return $merged;
			}

			// neither family matched - the generic tail below pins both sides
		}

		// a single call side runs the family compositions with the other
		// side's TYPE as the constant - the composed form of the old
		// normalization that moved the call to the left
		if ($unwrappedLeft instanceof Expr\FuncCall || $unwrappedRight instanceof Expr\FuncCall) {
			if ($unwrappedLeft instanceof Expr\FuncCall) {
				$familyTypes = $this->specifyFuncCallFamilies($left, $leftResult, $unwrappedLeft, $right, $rightType, $context, $evaluationScope, $leftArgResult);
			} else {
				$familyTypes = $this->specifyFuncCallFamilies($right, $rightResult, $unwrappedRight, $left, $leftType, $context, $evaluationScope, $rightArgResult);
			}
			if ($familyTypes === null) {
				return null;
			}
			if ($familyTypes !== false) {
				return $familyTypes;
			}
		}

		$decidedTypes = $this->specifyDecidedComparison($left, $right, $leftResult, $rightResult, $context, $evaluationScope, $identicalTypeCallback);
		if ($decidedTypes !== null) {
			return $decidedTypes;
		}

		$types = null;
		if (
			count($leftType->getFiniteTypes()) === 1
			|| (
				$context->true()
				&& $leftType->isConstantValue()->yes()
				&& !$rightType->equals($leftType)
				&& $rightType->isSuperTypeOf($leftType)->yes())
		) {
			$types = $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $right, $rightResult, $leftType, $context);
		}
		if (
			count($rightType->getFiniteTypes()) === 1
			|| (
				$context->true()
				&& $rightType->isConstantValue()->yes()
				&& !$leftType->equals($rightType)
				&& $leftType->isSuperTypeOf($rightType)->yes()
			)
		) {
			$leftTypes = $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $left, $leftResult, $rightType, $context);
			$types = $types !== null ? $types->unionWith($leftTypes) : $leftTypes;
		}

		if ($types !== null) {
			return $types;
		}

		$leftExprString = $this->exprPrinter->printExpr($unwrappedLeft);
		$rightExprString = $this->exprPrinter->printExpr($unwrappedRight);
		if ($leftExprString === $rightExprString) {
			if (!$unwrappedLeft instanceof Expr\Variable || !$unwrappedRight instanceof Expr\Variable) {
				return new SpecifiedTypes([], []);
			}
		}

		if ($context->true()) {
			return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $left, $leftResult, $rightType, $context)->unionWith(
				$this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $right, $rightResult, $leftType, $context),
			);
		} elseif ($context->false()) {
			return $this->defaultNarrowingHelper->toSureTypes($this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $left, $leftResult, $leftType, $context), $evaluationScope)
				->intersectWith($this->defaultNarrowingHelper->toSureTypes($this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $right, $rightResult, $rightType, $context), $evaluationScope));
		}

		return new SpecifiedTypes([], []);
	}

	/**
	 * New-world narrowing for `==` (and, via a negated context, `!=`):
	 * loose comparisons reduce to falsy-set pins, truthiness delegation, or
	 * the identical narrowing when coercion cannot differ - all composed
	 * from the operand results, no synthetic nodes. Uncovered shapes return
	 * null and fall back to the old-world Equal path.
	 */
	public function specifyEqual(
		NodeScopeResolver $nodeScopeResolver,
		Expr $left,
		Expr $right,
		ExpressionResult $leftResult,
		ExpressionResult $rightResult,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
		?ExpressionResult $leftArgResult,
		?ExpressionResult $rightArgResult,
	): ?SpecifiedTypes
	{
		if ($context->null()) {
			return null;
		}

		$identicalTypeCallback = fn (): Type => $this->richerScopeGetTypeHelper->getIdenticalResult($evaluationScope, new Expr\BinaryOp\Identical($left, $right), $nodeScopeResolver)->type;

		$unwrappedLeft = $left instanceof AlwaysRememberedExpr ? $left->getExpr() : $left;
		$unwrappedRight = $right instanceof AlwaysRememberedExpr ? $right->getExpr() : $right;
		$leftType = $this->literalType($unwrappedLeft) ?? $leftResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
		$rightType = $this->literalType($unwrappedRight) ?? $rightResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);

		$leftScalarValues = $leftType->getConstantScalarValues();
		$rightScalarValues = $rightType->getConstantScalarValues();
		if (count($leftScalarValues) === 1 && !$unwrappedRight instanceof Expr\ConstFetch) {
			$constantSideTypes = $this->specifyEqualAgainstConstantSide($nodeScopeResolver, $left, $right, $leftResult, $rightResult, $right, $rightResult, $leftScalarValues[0], $leftType, $rightType, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
			if ($constantSideTypes !== false) {
				return $constantSideTypes;
			}
		} elseif (count($rightScalarValues) === 1 && !$unwrappedLeft instanceof Expr\ConstFetch) {
			$constantSideTypes = $this->specifyEqualAgainstConstantSide($nodeScopeResolver, $left, $right, $leftResult, $rightResult, $left, $leftResult, $rightScalarValues[0], $rightType, $leftType, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
			if ($constantSideTypes !== false) {
				return $constantSideTypes;
			}
		}

		// a side that coerces to a known bool compares the other side's
		// truthiness - the literal-bool identical narrowing composes it
		$leftBool = $leftType->toBoolean();
		if (($leftBool->isTrue()->yes() || $leftBool->isFalse()->yes()) && $rightType->isBoolean()->yes()) {
			// the literal side of the delegation needs no result; the subject side is the right operand
			return $this->specifyIdentical($nodeScopeResolver, new Expr\ConstFetch(new Name($leftBool->isTrue()->yes() ? 'true' : 'false')), $right, $rightResult, $rightResult, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
		}
		$rightBool = $rightType->toBoolean();
		if (($rightBool->isTrue()->yes() || $rightBool->isFalse()->yes()) && $leftType->isBoolean()->yes()) {
			return $this->specifyIdentical($nodeScopeResolver, $left, new Expr\ConstFetch(new Name($rightBool->isTrue()->yes() ? 'true' : 'false')), $leftResult, $leftResult, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
		}

		// an empty constant array equals only empty countables
		if ($rightType->isArray()->yes() && $leftType->isConstantArray()->yes() && $leftType->isIterableAtLeastOnce()->no()) {
			return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $right, $rightResult, new NonEmptyArrayType(), $context->negate());
		}
		if ($leftType->isArray()->yes() && $rightType->isConstantArray()->yes() && $rightType->isIterableAtLeastOnce()->no()) {
			return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $left, $leftResult, new NonEmptyArrayType(), $context->negate());
		}

		// same-type sides cannot coerce - loose equals strict
		if (
			($leftType->isString()->yes() && $rightType->isString()->yes())
			|| ($leftType->isInteger()->yes() && $rightType->isInteger()->yes())
			|| ($leftType->isFloat()->yes() && $rightType->isFloat()->yes())
			|| ($leftType->isEnum()->yes() && $rightType->isEnum()->yes())
		) {
			return $this->specifyIdentical($nodeScopeResolver, $left, $right, $leftResult, $rightResult, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
		}

		$leftExprString = $this->exprPrinter->printExpr($left);
		$rightExprString = $this->exprPrinter->printExpr($right);
		if ($leftExprString === $rightExprString) {
			if (!$left instanceof Expr\Variable || !$right instanceof Expr\Variable) {
				return new SpecifiedTypes([], []);
			}
		}

		$leftTypes = $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $left, $leftResult, $leftType, $context);
		$rightTypes = $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $right, $rightResult, $rightType, $context);

		return $context->true()
			? $leftTypes->unionWith($rightTypes)
			: $this->defaultNarrowingHelper->toSureTypes($leftTypes, $evaluationScope)->intersectWith($this->defaultNarrowingHelper->toSureTypes($rightTypes, $evaluationScope));
	}

	/**
	 * Identity narrowing of a subject against a known constant type - the
	 * entry point for callers that hold no comparison node at all (the
	 * assign-time conditional holders compare the assigned expression with
	 * falsy sentinels). $constantExpr is only printed into reverse entries,
	 * never walked. Null means the shape is not composed and the caller
	 * keeps its old-world path.
	 *
	 * @param callable(): Type $identicalTypeCallback
	 */
	public function specifyIdenticalAgainstType(
		Expr $subject,
		ExpressionResult $subjectResult,
		Expr $constantExpr,
		Type $constantType,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
		?ExpressionResult $subjectArgResult,
		callable $identicalTypeCallback,
	): ?SpecifiedTypes
	{
		if ($context->null()) {
			return null;
		}

		if ($constantType->isNull()->yes()) {
			// unguarded like the null-literal slice - the subtraction entry
			// must keep firing conditional holders
			return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, new NullType(), $context);
		}

		$unwrappedSubject = $subject instanceof AlwaysRememberedExpr ? $subject->getExpr() : $subject;

		if ($constantType->isTrue()->yes() || $constantType->isFalse()->yes()) {
			$types = $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, new ConstantBooleanType($constantType->isTrue()->yes()), $context);
			if (!$context->true() && ($unwrappedSubject instanceof Expr\NullsafeMethodCall || $unwrappedSubject instanceof Expr\NullsafePropertyFetch)) {
				return $types;
			}

			$boolContext = $constantType->isTrue()->yes() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();

			return $types->unionWith($subjectResult->getSpecifiedTypesForScope(
				$evaluationScope,
				$context->true() ? $boolContext : $boolContext->negate(),
			));
		}

		if ($unwrappedSubject instanceof Expr\FuncCall) {
			$familyTypes = $this->specifyFuncCallFamilies($subject, $subjectResult, $unwrappedSubject, $constantExpr, $constantType, $context, $evaluationScope, $subjectArgResult);
			if ($familyTypes === null) {
				return null;
			}
			if ($familyTypes !== false) {
				return $familyTypes;
			}
		} elseif ($unwrappedSubject instanceof Expr\ClassConstFetch && $unwrappedSubject->class instanceof Expr) {
			return null;
		}

		if ($context->false()) {
			$identicalType = $identicalTypeCallback();
			$isTrue = $identicalType->isTrue()->yes();
			if ($isTrue || $identicalType->isFalse()->yes()) {
				$never = new NeverType();
				$contextForTypes = $isTrue ? $context->negate() : $context;

				return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $never, $contextForTypes)->unionWith(
					$this->defaultNarrowingHelper->createForSubject($constantExpr, $never, $contextForTypes, $evaluationScope),
				);
			}
		}

		$types = $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context);

		$subjectType = $subjectResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
		if (count($subjectType->getFiniteTypes()) === 1) {
			$types = $types->unionWith($this->defaultNarrowingHelper->createForSubject($constantExpr, $subjectType, $context, $evaluationScope));
		}

		return $types;
	}

	/**
	 * The == narrowing against a single-valued side: a family answer, null
	 * to fall back to the old-world path, or false when nothing matched and
	 * the caller continues with the coercion branches.
	 *
	 * @param callable(): Type $identicalTypeCallback
	 * @return SpecifiedTypes|false|null
	 */
	private function specifyEqualAgainstConstantSide(
		NodeScopeResolver $nodeScopeResolver,
		Expr $left,
		Expr $right,
		ExpressionResult $leftResult,
		ExpressionResult $rightResult,
		Expr $subject,
		ExpressionResult $subjectResult,
		mixed $value,
		Type $constantType,
		Type $otherType,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
		?ExpressionResult $leftArgResult,
		?ExpressionResult $rightArgResult,
		callable $identicalTypeCallback,
	): SpecifiedTypes|false|null
	{
		$unwrappedSubject = $subject instanceof AlwaysRememberedExpr ? $subject->getExpr() : $subject;

		if ($value === null) {
			return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, new UnionType([
				new NullType(),
				new ConstantBooleanType(false),
				new ConstantIntegerType(0),
				new ConstantFloatType(0.0),
				new ConstantStringType(''),
				new ConstantArrayType([], []),
			]), $context);
		}

		// a bool constant compares by the subject's truthiness
		if ($value === false) {
			return $subjectResult->getSpecifiedTypesForScope(
				$evaluationScope,
				$context->true() ? TypeSpecifierContext::createFalsey() : TypeSpecifierContext::createFalsey()->negate(),
			);
		}
		if ($value === true) {
			return $subjectResult->getSpecifiedTypesForScope(
				$evaluationScope,
				$context->true() ? TypeSpecifierContext::createTruthy() : TypeSpecifierContext::createTruthy()->negate(),
			);
		}

		/* There is a difference between php 7.x and 8.x on the equality
		 * behavior between zero and the empty string, so to be conservative
		 * we leave it untouched regardless of the language version */
		if ($value === 0 && !$otherType->isInteger()->yes() && !$otherType->isBoolean()->yes()) {
			$trueTypes = $context->true()
				? [new NullType(), new ConstantBooleanType(false), new ConstantIntegerType(0), new ConstantFloatType(0.0), new StringType()]
				: [new NullType(), new ConstantBooleanType(false), new ConstantIntegerType(0), new ConstantFloatType(0.0), new ConstantStringType('0')];

			return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, new UnionType($trueTypes), $context);
		}
		if ($value === '') {
			$trueTypes = $context->true()
				? [new NullType(), new ConstantBooleanType(false), new ConstantIntegerType(0), new ConstantFloatType(0.0), new ConstantStringType('')]
				: [new NullType(), new ConstantBooleanType(false), new ConstantStringType('')];

			return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, new UnionType($trueTypes), $context);
		}

		// loose equals strict for these call results and class names
		if (
			$unwrappedSubject instanceof Expr\FuncCall
			&& $unwrappedSubject->name instanceof Name
			&& !$unwrappedSubject->isFirstClassCallable()
			&& isset($unwrappedSubject->getArgs()[0])
		) {
			$funcName = $unwrappedSubject->name->toLowerString();
			if (in_array($funcName, ['gettype', 'get_class', 'get_debug_type'], true) && $constantType->isString()->yes()) {
				return $this->specifyIdentical($nodeScopeResolver, $left, $right, $leftResult, $rightResult, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
			}
			if ($context->true() && $funcName === 'preg_match' && (new ConstantIntegerType(1))->isSuperTypeOf($constantType)->yes()) {
				return $this->specifyIdentical($nodeScopeResolver, $left, $right, $leftResult, $rightResult, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
			}
		}
		if (
			$unwrappedSubject instanceof Expr\ClassConstFetch
			&& !($unwrappedSubject->name instanceof Expr)
			&& $unwrappedSubject->name->toLowerString() === 'class'
			&& $constantType->isString()->yes()
		) {
			return $this->specifyIdentical($nodeScopeResolver, $left, $right, $leftResult, $rightResult, $context, $evaluationScope, $leftArgResult, $rightArgResult, $identicalTypeCallback);
		}

		return false;
	}

	/**
	 * When the subject variable is provenance-recorded as holding the result
	 * of a pure call (`$v = count($x)`), the comparison against the constant
	 * also runs the call's own family narrowing - `$v == 3` narrows $x like
	 * `count($x) == 3` would. The recorded call comes from an earlier
	 * statement and has no stored results in this walk, so the families read
	 * the argument's current type from the evaluation scope's tracked state.
	 */
	private function specifyThroughResultProvenance(
		Expr $subject,
		Expr $constantExpr,
		Type $constantType,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
	): ?SpecifiedTypes
	{
		$unwrappedSubject = $subject instanceof AlwaysRememberedExpr ? $subject->getExpr() : $subject;
		if (!$unwrappedSubject instanceof Expr\Variable || !is_string($unwrappedSubject->name)) {
			return null;
		}

		$call = $evaluationScope->getResultProvenanceCall('$' . $unwrappedSubject->name);
		if ($call === null) {
			return null;
		}

		$familyTypes = $this->specifyFuncCallFamilies($call, null, $call, $constantExpr, $constantType, $context, $evaluationScope, null, true);
		if (!$familyTypes instanceof SpecifiedTypes) {
			return null;
		}

		return $familyTypes;
	}

	/**
	 * The function-family compositions, shared by the literal and the
	 * TYPE-based constant sides: a family answer, null to fall back to the
	 * old-world path, or false when no family matched and the caller narrows
	 * generically.
	 *
	 * @return SpecifiedTypes|false|null
	 */
	private function specifyFuncCallFamilies(
		Expr $subject,
		?ExpressionResult $subjectResult,
		Expr\FuncCall $call,
		Expr $constantExpr,
		Type $constantType,
		TypeSpecifierContext $context,
		MutatingScope $evaluationScope,
		?ExpressionResult $argResult,
		bool $argTypesFromScopeState = false,
	): SpecifiedTypes|false|null
	{
		if (!($call->name instanceof Name) || $call->isFirstClassCallable() || !isset($call->getArgs()[0])) {
			return false;
		}

		// preg_match(...) === 1 is the call's own truthy narrowing - the
		// type-specifying extensions narrow the by-ref \$matches argument
		if (
			$call->name->toLowerString() === 'preg_match'
		) {
			if ($context->true() && (new ConstantIntegerType(1))->isSuperTypeOf($constantType)->yes()) {
				if ($subjectResult === null) {
					return null;
				}

				return $subjectResult->getSpecifiedTypesForScope($evaluationScope, $context);
			}

			// other constants and contexts only pin the call below
		}

		// a trimmed string that is not '' was a non-empty string already
		if (
			in_array($call->name->toLowerString(), ['trim', 'ltrim', 'rtrim', 'chop', 'mb_trim', 'mb_ltrim', 'mb_rtrim'], true)
		) {
			if ($context->false()) {
				$constantStrings = $constantType->getConstantStrings();
				if (count($constantStrings) === 1 && $constantStrings[0]->getValue() === '') {
					$argExpr = $call->getArgs()[0]->value;
					$argType = $this->resolveFamilyArgType($call, $argResult, $argTypesFromScopeState, $evaluationScope);
					if ($argType === null) {
						return null;
					}
					if ($argType->isString()->yes()) {
						return $this->defaultNarrowingHelper->createForSubject(
							$argExpr,
							new IntersectionType([new StringType(), new AccessoryNonEmptyStringType()]),
							$context->negate(),
							$evaluationScope,
						);
					}
				}
			}

			// other constants and contexts only pin the call
		}

		// a known parent class narrows the argument to the child side of it
		if ($call->name->toLowerString() === 'get_parent_class') {
			if ($context->true()) {
				$constantStrings = $constantType->getConstantStrings();
				if (count($constantStrings) === 1 && $constantStrings[0]->getValue() !== '') {
					$argExpr = $call->getArgs()[0]->value;
					$argType = $this->resolveFamilyArgType($call, $argResult, $argTypesFromScopeState, $evaluationScope);
					if ($argType === null) {
						return null;
					}
					$objectType = new ObjectType($constantStrings[0]->getValue());
					$classStringType = new GenericClassStringType($objectType);

					if ($argType->isString()->yes()) {
						$narrowed = $classStringType;
					} elseif ($argType->isObject()->yes()) {
						$narrowed = $objectType;
					} else {
						$narrowed = TypeCombinator::union($objectType, $classStringType);
					}

					return $this->defaultNarrowingHelper->createForSubject($argExpr, $narrowed, $context, $evaluationScope);
				}
			}

			// other contexts and non-single class names only pin the call
		}

		// a string function whose result is a non-empty literal had a
		// non-empty (non-falsy for a non-falsy literal) string argument;
		// case-mapping functions pin the case accessory on the literal side
		if (
			in_array($call->name->toLowerString(), [
				'substr', 'strstr', 'stristr', 'strchr', 'strrchr', 'strtolower', 'strtoupper', 'ucfirst', 'lcfirst',
				'mb_substr', 'mb_strstr', 'mb_stristr', 'mb_strchr', 'mb_strrchr', 'mb_strtolower', 'mb_strtoupper', 'mb_ucfirst', 'mb_lcfirst',
				'ucwords', 'mb_convert_case', 'mb_convert_kana',
			], true)
		) {
			if ($context->truthy() && $constantType->isNonEmptyString()->yes()) {
				$argExpr = $call->getArgs()[0]->value;
				$argType = $this->resolveFamilyArgType($call, $argResult, $argTypesFromScopeState, $evaluationScope);
				if ($argType === null) {
					return null;
				}

				if ($argType->isString()->yes()) {
					$types = new SpecifiedTypes();
					$funcName = $call->name->toLowerString();
					if (in_array($funcName, ['strtolower', 'mb_strtolower'], true)) {
						$types = $this->defaultNarrowingHelper->createForSubject($constantExpr, TypeCombinator::intersect($constantType, new AccessoryLowercaseStringType()), $context, $evaluationScope);
					} elseif (in_array($funcName, ['strtoupper', 'mb_strtoupper'], true)) {
						$types = $this->defaultNarrowingHelper->createForSubject($constantExpr, TypeCombinator::intersect($constantType, new AccessoryUppercaseStringType()), $context, $evaluationScope);
					}

					$accessory = $constantType->isNonFalsyString()->yes()
						? new AccessoryNonFalsyStringType()
						: new AccessoryNonEmptyStringType();

					return $types->unionWith($this->defaultNarrowingHelper->createForSubject(
						$argExpr,
						TypeCombinator::intersect($argType, $accessory),
						$context,
						$evaluationScope,
					));
				}
			}

			// a non-string argument, an empty literal or a non-truthy
			// context only pins the call
		}

		// count($x) === N reconstructs the array shape by its size - before
		// the decided guard so exhaustive size switches keep collapsing
		if (
			in_array($call->name->toLowerString(), ['count', 'sizeof'], true)
		) {
			if (!$constantType->isInteger()->yes()) {
				return null;
			}

			$argExpr = $call->getArgs()[0]->value;
			if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($constantType)->yes()) {
				return $this->defaultNarrowingHelper->createForSubject($argExpr, new NeverType(), $context, $evaluationScope);
			}

			$argType = $this->resolveFamilyArgType($call, $argResult, $argTypesFromScopeState, $evaluationScope);
			if ($argType === null) {
				return null;
			}

			if ((new ConstantIntegerType(0))->isSuperTypeOf($constantType)->yes()) {
				$newArgType = $context->truthy() && !$argType->isArray()->yes()
					? new UnionType([new ObjectType(Countable::class), new ConstantArrayType([], [])])
					: new ConstantArrayType([], []);

				return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context)->unionWith(
					$this->defaultNarrowingHelper->createForSubject($argExpr, $newArgType, $context, $evaluationScope),
				);
			}

			$countTypes = $this->countNarrowingHelper->specifyCountSize($call, $argType, $constantType, $context, $evaluationScope, $call);
			if ($countTypes !== null) {
				// the old path pinned the call only through the remembered
				// wrapper; the composed pin covers wrapper and call alike
				if ($subject !== $call) {
					return $countTypes->unionWith($this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context));
				}

				return $countTypes;
			}

			if ($context->truthy() && $argType->isArray()->yes()) {
				$types = $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context);
				if (IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($constantType)->yes()) {
					return $types->unionWith(
						$this->defaultNarrowingHelper->createForSubject($argExpr, new NonEmptyArrayType(), $context, $evaluationScope),
					);
				}

				return $types;
			}

			// a non-array argument in a non-truthy context only pins the call
		}

		// strlen($x) === 0 empties $x; === N >= 1 makes it non-empty in the
		// truthy direction (>= 2 non-falsy) - before the decided guard
		if (
			in_array($call->name->toLowerString(), ['strlen', 'mb_strlen'], true)
		) {
			if (count($call->getArgs()) !== 1 || !$constantType->isInteger()->yes()) {
				return null;
			}

			$argExpr = $call->getArgs()[0]->value;
			if (IntegerRangeType::fromInterval(null, -1)->isSuperTypeOf($constantType)->yes()) {
				return $this->defaultNarrowingHelper->createForSubject($argExpr, new NeverType(), $context, $evaluationScope);
			}

			if ((new ConstantIntegerType(0))->isSuperTypeOf($constantType)->yes()) {
				return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context)->unionWith(
					$this->defaultNarrowingHelper->createForSubject($argExpr, new ConstantStringType(''), $context, $evaluationScope),
				);
			}

			if ($context->truthy() && IntegerRangeType::fromInterval(1, null)->isSuperTypeOf($constantType)->yes()) {
				$argType = $this->resolveFamilyArgType($call, $argResult, $argTypesFromScopeState, $evaluationScope);
				if ($argType === null) {
					return null;
				}
				if ($argType->isString()->yes()) {
					$accessory = IntegerRangeType::fromInterval(2, null)->isSuperTypeOf($constantType)->yes()
						? new AccessoryNonFalsyStringType()
						: new AccessoryNonEmptyStringType();

					return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context)->unionWith(
						$this->defaultNarrowingHelper->createForSubject($argExpr, $accessory, $context, $evaluationScope),
					);
				}
			}

			// a non-string argument or a falsey non-zero size only pins the call
		}

		// gettype($x) === 'string' narrows $x by the named type in either
		// direction - before the decided-comparison guard, like the old block
		if (
			$call->name->toLowerString() === 'gettype'
		) {
			$constantStrings = $constantType->getConstantStrings();
			if (count($constantStrings) > 1) {
				// a union of type names narrows by the intersection of the
				// per-name narrowings
				$intersectedTypes = null;
				foreach ($constantStrings as $constantString) {
					$mapped = $this->getTypeFromGettypeStringValue($constantString->getValue());
					if ($mapped === null) {
						continue;
					}
					$one = $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantString, $context)->unionWith(
						$this->defaultNarrowingHelper->createForSubject($call->getArgs()[0]->value, $mapped, $context, $evaluationScope),
					);
					$intersectedTypes = $intersectedTypes === null ? $one : $intersectedTypes->intersectWith($one);
				}
				if ($intersectedTypes !== null) {
					return $intersectedTypes;
				}

				// no known type names - only pin the call
			}
			if (count($constantStrings) === 1) {
					$gettypeNarrowedType = $this->getTypeFromGettypeStringValue($constantStrings[0]->getValue());
				if ($gettypeNarrowedType !== null) {
					return $this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context)->unionWith(
						$this->defaultNarrowingHelper->createForSubject($call->getArgs()[0]->value, $gettypeNarrowedType, $context, $evaluationScope),
					);
				}
				// an unknown type-name string only pins the call itself below
			}

			// a non-constant string side only pins the call
		}

		// get_class($o) === 'Foo' pins $o to a final Foo when the comparison
		// holds; outside the true context only the call itself narrows
		if (in_array($call->name->toLowerString(), ['get_class', 'get_debug_type'], true) && $context->true()) {
			$narrowedObjectType = null;
			$constantStrings = $constantType->getConstantStrings();
			if (count($constantStrings) === 1 && $this->reflectionProvider->hasClass($constantStrings[0]->getValue())) {
				$narrowedObjectType = new ObjectType($constantStrings[0]->getValue(), classReflection: $this->reflectionProvider->getClass($constantStrings[0]->getValue())->asFinal());
			} elseif ($constantType->getClassStringObjectType()->isObject()->yes()) {
				$narrowedObjectType = $constantType->getClassStringObjectType();
			}

			if ($narrowedObjectType !== null) {
				return $this->defaultNarrowingHelper->createForSubject(
					$call->getArgs()[0]->value,
					$narrowedObjectType,
					$context,
					$evaluationScope,
				)->unionWith($this->defaultNarrowingHelper->createSubjectTypes($evaluationScope, $subject, $subjectResult, $constantType, $context));
			}
		}

		return false;
	}

	/**
	 * The first argument's current type for the family compositions: from the
	 * captured operand result or - for a provenance-recorded call from an
	 * earlier statement, which has no captured results in this comparison -
	 * from the evaluation scope's tracked state. Null means unknown and the
	 * family falls back to the old-world path.
	 */
	private function resolveFamilyArgType(Expr\FuncCall $call, ?ExpressionResult $argResult, bool $argTypesFromScopeState, MutatingScope $evaluationScope): ?Type
	{
		if ($argTypesFromScopeState) {
			return $evaluationScope->getStateType($call->getArgs()[0]->value);
		}
		if ($argResult === null) {
			return null;
		}

		return $argResult->getTypeOnScope($evaluationScope, $evaluationScope->nativeTypesPromoted);
	}

	/**
	 * The first argument's stored result of a (possibly remembered) call
	 * operand, captured by the seams at create time - the composed function
	 * families read it instead of the asking scope's storage stack (ask-time
	 * state that differs between main-pass and post-walk asks and would
	 * break memoizing the narrowing per context). Capturing the result, not
	 * the storage, keeps retention bounded.
	 */
	public function captureFirstArgResult(Expr $side, ExpressionResultStorage $storage): ?ExpressionResult
	{
		$unwrapped = $side instanceof AlwaysRememberedExpr ? $side->getExpr() : $side;
		if (!$unwrapped instanceof Expr\FuncCall || $unwrapped->isFirstClassCallable() || !isset($unwrapped->getArgs()[0])) {
			return null;
		}

		return $storage->findExpressionResult($unwrapped->getArgs()[0]->value);
	}

	/** The static type of a literal node - no result or scope needed. */
	private function literalType(Expr $expr): ?Type
	{
		if ($expr instanceof Scalar\Int_) {
			return new ConstantIntegerType($expr->value);
		}
		if ($expr instanceof Scalar\Float_) {
			return new ConstantFloatType($expr->value);
		}
		if ($expr instanceof Scalar\String_) {
			return new ConstantStringType($expr->value);
		}
		if ($expr instanceof Expr\ConstFetch) {
			$name = $expr->name->toLowerString();
			if ($name === 'true') {
				return new ConstantBooleanType(true);
			}
			if ($name === 'false') {
				return new ConstantBooleanType(false);
			}
			if ($name === 'null') {
				return new NullType();
			}
		}

		return null;
	}

	private function isScalarLiteral(Expr $expr): bool
	{
		if ($expr instanceof Scalar\Int_ || $expr instanceof Scalar\String_ || $expr instanceof Scalar\Float_) {
			return true;
		}

		// Foo::BAR, Suit::Hearts, Foo::class - but not $a::class, whose
		// narrowing works on $a (an old-world block, not ported yet)
		return $expr instanceof Expr\ClassConstFetch
			&& $expr->class instanceof Name
			&& !$expr->name instanceof Expr;
	}

	/**
	 * Subjects whose comparison against a constant narrows more than the
	 * subject expression itself stay on the old-world path for now: function
	 * calls narrow their arguments (count($a) === 0 empties $a), `$a::class`
	 * narrows $a. The null comparison is fully composed (the array_key_first
	 * family narrows its argument through the FuncCall's createTypesCallback)
	 * and does not consult this.
	 */
	private function isSubjectCoveredAgainstConstant(Expr $subject): bool
	{
		$unwrapped = $subject instanceof AlwaysRememberedExpr ? $subject->getExpr() : $subject;
		if ($unwrapped instanceof Expr\FuncCall) {
			return false;
		}

		return !($unwrapped instanceof Expr\ClassConstFetch && $unwrapped->class instanceof Expr);
	}

}
