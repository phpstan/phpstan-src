<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use Closure;
use PhpParser\Node;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\CallLike;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Expr\MethodCall;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\StaticPropertyFetch;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;
use PhpParser\Node\VarLikeIdentifier;
use PHPStan\Analyser\ExpressionResult;
use PHPStan\Analyser\ExpressionResultStorage;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\NullsafeOperatorHelper;
use PHPStan\Analyser\Scope;
use PHPStan\Analyser\SpecifiedTypes;
use PHPStan\Analyser\TypeSpecifierContext;
use PHPStan\DependencyInjection\AutowiredParameter;
use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Node\Expr\TypeExpr;
use PHPStan\Node\IssetExpr;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\ParametersAcceptor;
use PHPStan\Reflection\ReflectionProvider;
use PHPStan\Reflection\ResolvedFunctionVariant;
use PHPStan\Rules\Arrays\AllowedArrayKeysTypes;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Accessory\HasOffsetType;
use PHPStan\Type\Accessory\HasPropertyType;
use PHPStan\Type\Accessory\NonEmptyArrayType;
use PHPStan\Type\ConditionalTypeForParameter;
use PHPStan\Type\Constant\ConstantBooleanType;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\Generic\TemplateType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\NullType;
use PHPStan\Type\ObjectWithoutClassType;
use PHPStan\Type\StaticTypeFactory;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeTraverser;
use function array_key_exists;
use function array_last;
use function array_map;
use function array_reverse;
use function count;
use function in_array;
use function is_string;
use function spl_object_id;
use function strtolower;
use function substr;

/**
 * New-world replacement for TypeSpecifier::handleDefaultTruthyOrFalseyContext():
 * the default narrowing of an expression used in a boolean context.
 *
 * Unlike the old world there is no nullsafe short-circuiting here: expressions
 * process inside-out, so only the nullsafe handlers ever see a `?->` - they
 * emit the plain-chain variant alongside their own key once, and every parent
 * simply composes their results. No recursive chain-walking, no type ask.
 */
#[AutowiredService]
final class DefaultNarrowingHelper
{

	public function __construct(
		private ExprPrinter $exprPrinter,
		#[AutowiredParameter]
		private bool $rememberPossiblyImpureFunctionValues,
		private ReflectionProvider $reflectionProvider,
	)
	{
	}

	/**
	 * Narrows an arbitrary (often synthetic) node in the given boolean context by
	 * processing it on demand and asking its result, the inside-out replacement
	 * for TypeSpecifier::specifyTypesInCondition() on the handler path. A node not
	 * stored is processed on demand; a node whose handler wired no specifyTypesCallback
	 * (or no handler) yields the default truthy/falsey narrowing.
	 */
	public function specifyTypesForNode(Scope $scope, Expr $node, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($node instanceof Expr\CallLike && $node->isFirstClassCallable()) {
			return (new SpecifiedTypes([], []))->setRootExpr($node);
		}

		return $scope->toMutatingScope()->specifyTypesOfNewWorldHandlerNode($node, $context);
	}

	public function specifyDefaultTypes(Expr $expr, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($context->null()) {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		if (!$context->truthy()) {
			$removedType = StaticTypeFactory::truthy();
		} elseif (!$context->falsey()) {
			$removedType = StaticTypeFactory::falsey();
		} else {
			return (new SpecifiedTypes([], []))->setRootExpr($expr);
		}

		return (new SpecifiedTypes(sureNotTypes: [
			$this->exprPrinter->printExpr($expr) => [$expr, $removedType],
		]))->setRootExpr($expr);
	}

	/**
	 * Converts sure-not entries to sure form against the given evaluation
	 * scope (position-fixed, captured at compose time) - for the decided
	 * comparison paths whose consumers need a concrete sure type. This is NOT
	 * the deleted SpecifiedTypes::normalize(): the scope here is the
	 * narrowing's own evaluation position, never the application point.
	 */
	public function toSureTypes(SpecifiedTypes $types, MutatingScope $evaluationScope): SpecifiedTypes
	{
		$sureTypes = $types->getSureTypes();

		foreach ($types->getSureNotTypes() as $exprString => [$exprNode, $sureNotType]) {
			if (!isset($sureTypes[$exprString])) {
				$sureTypes[$exprString] = [$exprNode, TypeCombinator::remove($evaluationScope->getStateType($exprNode), $sureNotType)];
				continue;
			}

			$sureTypes[$exprString][1] = TypeCombinator::remove($sureTypes[$exprString][1], $sureNotType);
		}

		$result = new SpecifiedTypes($sureTypes, []);
		if ($types->shouldOverwrite()) {
			$result = $result->setAlwaysOverwriteTypes();
		}

		return $result->setRootExpr($types->getRootExpr());
	}

	/**
	 * The new-world counterpart of TypeSpecifier::create() for a subject the
	 * calling handler has already processed. The subject's own result says how
	 * a type constraint on it translates into entries (an assignment fans out
	 * to the assigned variable, a coalesce delegates to its left side); without
	 * a createTypesCallback the entries are composed here from the result's own
	 * facts: a call whose execution is (possibly) impure gets none, a chain
	 * containing a nullsafe additionally narrows its short-circuited plain twin.
	 * TypeSpecifier::create()/createForExpr() are never reached - their
	 * old-world machinery re-derives from the scope what the result already
	 * carries.
	 */
	public function createSubjectTypes(MutatingScope $s, Expr $subject, ?ExpressionResult $subjectResult, Type $type, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($subjectResult !== null) {
			$createdTypes = $subjectResult->getCreatedTypesForScope($s, $type, $context);
			if ($createdTypes !== null) {
				return $createdTypes;
			}
		}

		return $this->createSubjectTypesFromResultState($s, $subject, $subjectResult, $type, $context);
	}

	/**
	 * The fallback entry building - createSubjectTypes() without consulting the
	 * result's createTypesCallback. A handler's OWN createTypesCallback delegates
	 * here with its stored result so the impure gate and the nullsafe-chain fan
	 * still read the result state without re-entering itself.
	 */
	public function createSubjectTypesFromResultState(MutatingScope $s, Expr $subject, ?ExpressionResult $subjectResult, Type $type, TypeSpecifierContext $context): SpecifiedTypes
	{
		if ($subject instanceof Expr\Instanceof_ || $subject instanceof Expr\List_) {
			return new SpecifiedTypes([], []);
		}

		$exprToSpecify = $subject;
		if ($subjectResult !== null) {
			// a call whose own execution is (possibly) impure must not get a
			// remembered type - the gate reads the result's own impure point
			// instead of re-asking reflection like the old create() did
			if (
				$subject instanceof Expr\FuncCall
				|| $subject instanceof Expr\MethodCall
				|| $subject instanceof Expr\StaticCall
				|| $subject instanceof Expr\NullsafeMethodCall
			) {
				foreach ($subjectResult->getImpurePoints() as $impurePoint) {
					if ($impurePoint->getNode() !== $subject) {
						continue;
					}
					if ($impurePoint->isCertain() || !$this->rememberPossiblyImpureFunctionValues) {
						// the call's value is not remembered, but a nullsafe
						// receiver chain still narrows not-null: the chain must
						// have evaluated for the (impure) call to produce any
						// non-null value at all (mirrors the old createForExpr()
						// returning createNullsafeTypes() from its impure branch)
						if ($subjectResult->containsNullsafe() && $this->nullsafeShortCircuitRuledOut($s, $subjectResult, $type, $context)) {
							return $this->createFirstNullsafeReceiverTypes($s, $subject) ?? new SpecifiedTypes([], []);
						}

						return new SpecifiedTypes([], []);
					}

					break;
				}
			}

			// a chain containing a nullsafe narrows its short-circuited plain
			// twin too, when the constraint (or the subject's own type) rules
			// the short-circuit null out - the containsNullsafe flag and the
			// memoized result type replace the old scope-type probe
			if ($subjectResult->containsNullsafe()) {
				$nullRuledOut = $this->nullsafeShortCircuitRuledOut($s, $subjectResult, $type, $context);

				if ($nullRuledOut) {
					$exprToSpecify = NullsafeOperatorHelper::getNullsafeShortcircuitedExpr($subject);
					// a plain fetch/call wrapped AROUND a nullsafe chain has no
					// createTypesCallback of its own - fan "the chain did not
					// short-circuit" through the first nullsafe below it, like
					// the old create()'s createNullsafeTypes() union
					$nullsafeFanTypes = $this->createFirstNullsafeReceiverTypes($s, $subject);
				}
			}
		}

		$sureTypes = [];
		$sureNotTypes = [];
		if ($context->false()) {
			$sureNotTypes[$this->exprPrinter->printExpr($exprToSpecify)] = [$exprToSpecify, $type];
			if ($exprToSpecify !== $subject) {
				$sureNotTypes[$this->exprPrinter->printExpr($subject)] = [$subject, $type];
			}
		} elseif ($context->true()) {
			$sureTypes[$this->exprPrinter->printExpr($exprToSpecify)] = [$exprToSpecify, $type];
			if ($exprToSpecify !== $subject) {
				$sureTypes[$this->exprPrinter->printExpr($subject)] = [$subject, $type];
			}
		}

		$result = new SpecifiedTypes($sureTypes, $sureNotTypes);
		if (isset($nullsafeFanTypes)) {
			$result = $result->unionWith($nullsafeFanTypes);
		}

		return $result;
	}

	/**
	 * specifyDefaultTypes() plus the nullsafe receiver fan: the default truthy
	 * narrowing of a chain containing a nullsafe also narrows its receivers
	 * not-null (the old-world truthy default routed through create()'s
	 * nullsafe fan). The stored result is looked up at ask time.
	 */
	public function specifyDefaultTypesWithNullsafeFan(Expr $expr, TypeSpecifierContext $context, MutatingScope $beforeScope, bool $nativeTypesPromoted): SpecifiedTypes
	{
		$default = $this->specifyDefaultTypes($expr, $context);
		if (!$context->truthy() || $context->falsey()) {
			return $default;
		}

		$s = $nativeTypesPromoted ? $beforeScope->doNotTreatPhpDocTypesAsCertain() : $beforeScope;
		$storage = $s->getCurrentExpressionResultStorage();
		$result = $storage !== null ? $storage->findExpressionResult($expr) : null;
		if ($result === null) {
			return $default;
		}

		$fan = $this->createNullsafeReceiverOnlyTypes($s, $expr, $result, StaticTypeFactory::falsey(), TypeSpecifierContext::createFalse());

		return $default->unionWith($fan)->setRootExpr($expr);
	}

	/**
	 * Only the nullsafe receiver fan of a subject whose own value must not be
	 * remembered (an impure or otherwise non-narrowable call): the receiver
	 * chain still narrows not-null when the constraint rules the
	 * short-circuit null out - the chain must have evaluated for the call to
	 * produce any non-null value at all.
	 */
	public function createNullsafeReceiverOnlyTypes(MutatingScope $s, Expr $subject, ?ExpressionResult $subjectResult, Type $type, TypeSpecifierContext $context): SpecifiedTypes
	{
		if (
			$subjectResult === null
			|| !$subjectResult->containsNullsafe()
			|| !$this->nullsafeShortCircuitRuledOut($s, $subjectResult, $type, $context)
		) {
			return new SpecifiedTypes([], []);
		}

		return $this->createFirstNullsafeReceiverTypes($s, $subject) ?? new SpecifiedTypes([], []);
	}

	/**
	 * Whether the constraint (or the subject's own type) rules the nullsafe
	 * short-circuit null out, so the chain's receivers can narrow not-null.
	 */
	private function nullsafeShortCircuitRuledOut(MutatingScope $s, ExpressionResult $subjectResult, Type $type, TypeSpecifierContext $context): bool
	{
		if ($context->true()) {
			return $type->isNull()->no() || ($s->nativeTypesPromoted ? $subjectResult->getNativeType() : $subjectResult->getType())->isNull()->no();
		}
		if ($context->false()) {
			return TypeCombinator::containsNull($type) || ($s->nativeTypesPromoted ? $subjectResult->getNativeType() : $subjectResult->getType())->isNull()->no();
		}

		return false;
	}

	/**
	 * Narrows the first nullsafe link below a plain fetch/call wrapper to
	 * "not null" (composing through its stored result, so its own receiver
	 * chain fans too) - the wrapper spine itself contributes nothing.
	 */
	private function createFirstNullsafeReceiverTypes(MutatingScope $s, Expr $expr): ?SpecifiedTypes
	{
		while (true) {
			if ($expr instanceof Expr\NullsafePropertyFetch || $expr instanceof Expr\NullsafeMethodCall) {
				$storage = $s->getCurrentExpressionResultStorage();

				return $this->createSubjectTypes(
					$s,
					$expr,
					$storage !== null ? $storage->findExpressionResult($expr) : null,
					new NullType(),
					TypeSpecifierContext::createFalse(),
				);
			}

			if ($expr instanceof PropertyFetch || $expr instanceof MethodCall || $expr instanceof Expr\ArrayDimFetch) {
				$expr = $expr->var;
				continue;
			}

			if (($expr instanceof Expr\StaticPropertyFetch || $expr instanceof Expr\StaticCall) && $expr->class instanceof Expr) {
				$expr = $expr->class;
				continue;
			}

			return null;
		}
	}

	/**
	 * The inside-out create() for a raw subject: narrows it through its own stored
	 * result's createTypesCallback, falling back to create() when there is none.
	 * When the caller already holds the subject's result (e.g. an operand a parent
	 * handler just processed) it passes a $resultFor lookup so composition uses that
	 * captured result directly instead of a storage lookup - so a remembered-wrapper
	 * operand fans out to wrapper + inner without the caller unwrapping it.
	 *
	 * @param (Closure(Expr): ?ExpressionResult)|null $resultFor
	 */
	public function createForSubject(Expr $subject, Type $type, TypeSpecifierContext $context, Scope $scope, ?Closure $resultFor = null): SpecifiedTypes
	{
		$mutatingScope = $scope->toMutatingScope();
		$subjectResult = $resultFor !== null ? $resultFor($subject) : null;

		$storage = $mutatingScope->getCurrentExpressionResultStorage();

		return $this->createSubjectTypes(
			$mutatingScope,
			$subject,
			$subjectResult ?? ($storage !== null ? $storage->findExpressionResult($subject) : null),
			$type,
			$context,
		);
	}

	/**
	 * Captures the stored ExpressionResults of an isset/empty/?? subject's
	 * chain links (the results, not the storage - no reference cycle) so
	 * narrowing callbacks read their types instead of re-walking the chain.
	 *
	 * @param array<int, ExpressionResult> $chainResults
	 */
	public function captureChainResults(Expr $node, ExpressionResultStorage $storage, array &$chainResults): void
	{
		$result = $storage->findExpressionResult($node);
		if ($result !== null) {
			$chainResults[spl_object_id($node)] = $result;
		}

		if ($node instanceof ArrayDimFetch) {
			$this->captureChainResults($node->var, $storage, $chainResults);
			if ($node->dim !== null) {
				$this->captureChainResults($node->dim, $storage, $chainResults);
			}
		} elseif ($node instanceof PropertyFetch) {
			$this->captureChainResults($node->var, $storage, $chainResults);
		} elseif ($node instanceof StaticPropertyFetch && $node->class instanceof Expr) {
			$this->captureChainResults($node->class, $storage, $chainResults);
		}
	}

	/**
	 * The chain-link type reader for the captured results: every link resolves
	 * through its captured result on the asking scope (honouring narrowing) -
	 * captureChainResults() captured the whole chain from the walk's storage.
	 *
	 * @param array<int, ExpressionResult> $chainResults
	 * @return Closure(Expr): Type
	 */
	public function buildChainTypeReader(array $chainResults, MutatingScope $s): Closure
	{
		return static function (Expr $e) use ($chainResults, $s): Type {
			$result = $chainResults[spl_object_id($e)] ?? null;
			if ($result === null) {
				throw new ShouldNotHappenException();
			}

			return $result->getTypeOnScope($s, $s->nativeTypesPromoted);
		};
	}

	/**
	 * The truthy narrowing of isset($issetExpr), composed from the subject's
	 * chain: per-link HasOffset/NonEmptyArray/HasProperty facts plus a not-null
	 * entry for every link - exactly what the Isset_ handler emits in the true
	 * context. Lets ?? narrow its left side without synthesizing an Isset_ node
	 * and re-walking the chain on demand.
	 *
	 * @param Closure(Expr): Type $readType
	 */
	public function createIssetTruthyChainTypes(MutatingScope $s, Expr $issetExpr, Closure $readType, Expr $rootExpr, TypeSpecifierContext $context): SpecifiedTypes
	{
		$tmpVars = [$issetExpr];
		while (
			$issetExpr instanceof ArrayDimFetch
			|| $issetExpr instanceof PropertyFetch
			|| (
				$issetExpr instanceof StaticPropertyFetch
				&& $issetExpr->class instanceof Expr
			)
		) {
			if ($issetExpr instanceof StaticPropertyFetch) {
				/** @var Expr $issetExpr */
				$issetExpr = $issetExpr->class;
			} else {
				$issetExpr = $issetExpr->var;
			}
			$tmpVars[] = $issetExpr;
		}
		$vars = array_reverse($tmpVars);

		$types = new SpecifiedTypes();
		foreach ($vars as $var) {

			if ($var instanceof Expr\Variable && is_string($var->name)) {
				if ($s->hasVariableType($var->name)->no()) {
					return (new SpecifiedTypes([], []))->setRootExpr($rootExpr);
				}
			}

			if (
				$var instanceof ArrayDimFetch
				&& $var->dim !== null
				&& !$readType($var->var) instanceof MixedType
			) {
				$dimType = $readType($var->dim);

				if ($dimType instanceof ConstantIntegerType || $dimType instanceof ConstantStringType) {
					$types = $types->unionWith(
						$this->createForSubject(
							$var->var,
							new HasOffsetType($dimType),
							$context,
							$s,
						)->setRootExpr($rootExpr),
					);
				} else {
					$varType = $readType($var->var);

					$narrowedKey = AllowedArrayKeysTypes::narrowOffsetKeyType($varType, $dimType);
					if ($narrowedKey !== null) {
						$types = $types->unionWith(
							$this->createForSubject(
								$var->dim,
								$narrowedKey,
								$context,
								$s,
							)->setRootExpr($rootExpr),
						);
					}

					if ($varType->isArray()->yes()) {
						$types = $types->unionWith(
							$this->createForSubject(
								$var->var,
								new NonEmptyArrayType(),
								$context,
								$s,
							)->setRootExpr($rootExpr),
						);
					}
				}
			}

			if (
				$var instanceof PropertyFetch
				&& $var->name instanceof Identifier
			) {
				$types = $types->unionWith(
					$this->createForSubject($var->var, new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType($var->name->toString()),
					]), TypeSpecifierContext::createTruthy(), $s)->setRootExpr($rootExpr),
				);
			} elseif (
				$var instanceof StaticPropertyFetch
				&& $var->class instanceof Expr
				&& $var->name instanceof VarLikeIdentifier
			) {
				$types = $types->unionWith(
					$this->createForSubject($var->class, new IntersectionType([
						new ObjectWithoutClassType(),
						new HasPropertyType($var->name->toString()),
					]), TypeSpecifierContext::createTruthy(), $s)->setRootExpr($rootExpr),
				);
			}

			$types = $types->unionWith(
				$this->createForSubject($var, new NullType(), TypeSpecifierContext::createFalse(), $s)->setRootExpr($rootExpr),
			);
		}

		return $types;
	}

	/**
	 * The non-true narrowing of a single isset() subject, composed from its
	 * captured result - shared by IssetHandler's paths and empty()'s disjunction.
	 *
	 * @param callable(Expr): Type $readType
	 */
	public function createIssetSingleSubjectNonTrueTypes(
		MutatingScope $s,
		Expr $issetExpr,
		ExpressionResult $varResult,
		callable $readType,
		TypeSpecifierContext $context,
		Expr $rootExpr,
	): SpecifiedTypes
	{
		$isset = $varResult->getIssetabilityResolution($s, false)->isSet(static fn (): bool => true);

		if ($isset === false) {
			return new SpecifiedTypes();
		}

		$type = $readType($issetExpr);
		$isNullable = !$type->isNull()->no();
		$exprType = $this->createForSubject(
			$issetExpr,
			new NullType(),
			$context->negate(),
			$s,
		)->setRootExpr($rootExpr);

		if ($issetExpr instanceof Expr\Variable && is_string($issetExpr->name)) {
			if ($isset === true) {
				if ($isNullable) {
					return $exprType;
				}

				// variable cannot exist in !isset()
				return $exprType->unionWith($this->createForSubject(
					new IssetExpr($issetExpr),
					new NullType(),
					$context,
					$s,
				))->setRootExpr($rootExpr);
			}

			if ($isNullable) {
				// reduces variable certainty to maybe
				return $exprType->unionWith($this->createForSubject(
					new IssetExpr($issetExpr),
					new NullType(),
					$context->negate(),
					$s,
				))->setRootExpr($rootExpr);
			}

			// variable cannot exist in !isset()
			return $this->createForSubject(
				new IssetExpr($issetExpr),
				new NullType(),
				$context,
				$s,
			)->setRootExpr($rootExpr);
		}

		if ($isNullable && $isset === true) {
			return $exprType;
		}

		// A maybe verdict on a native-typed property whose inner chain is fully
		// set can only mean "nullable value" or "maybe uninitialized". Reading an
		// uninitialized typed property throws instead of yielding a value, so in
		// the !isset() branch any read that completes yields null - the null pin
		// is sound for both.
		if ($isset === null && $isNullable) {
			$resolution = $varResult->getIssetabilityResolution($s, false);
			$link = $resolution->getLink();
			$inner = $resolution->getInner();
			if (
				$link->isProperty()
				&& $link->isReflectionNative()
				&& $link->hasNativeType()
				&& !$link->isVirtual()->yes()
				&& ($inner === null || $inner->isSet(static fn (): bool => true) === true)
			) {
				return $exprType;
			}
		}

		if (
			$issetExpr instanceof ArrayDimFetch
			&& $issetExpr->dim !== null
			// When the var is itself an offset access (a nested isset like
			// $r['K']['Port']), narrowing it in the falsey branch leaks the
			// intermediate offset's existence into the enclosing scope.
			&& !($issetExpr->var instanceof ArrayDimFetch)
		) {
			$varType = $readType($issetExpr->var);
			if (!$varType instanceof MixedType) {
				$dimType = $readType($issetExpr->dim);

				if ($dimType instanceof ConstantIntegerType || $dimType instanceof ConstantStringType) {
					$constantArrays = $varType->getConstantArrays();
					$typesToRemove = [];
					foreach ($constantArrays as $constantArray) {
						$hasOffset = $constantArray->hasOffsetValueType($dimType);
						if (!$hasOffset->yes() || !$constantArray->getOffsetValueType($dimType)->isNull()->no()) {
							continue;
						}

						$typesToRemove[] = $constantArray;
					}

					if ($typesToRemove !== []) {
						$typeToRemove = TypeCombinator::union(...$typesToRemove);

						$result = $this->createForSubject(
							$issetExpr->var,
							$typeToRemove,
							TypeSpecifierContext::createFalse(),
							$s,
						)->setRootExpr($rootExpr);

						if ($s->hasExpressionType($issetExpr->var)->maybe()) {
							$result = $result->unionWith(
								$this->createForSubject(
									new IssetExpr($issetExpr->var),
									new NullType(),
									TypeSpecifierContext::createTruthy(),
									$s,
								)->setRootExpr($rootExpr),
							);
						}

						return $result;
					}
				}
			}
		}

		return new SpecifiedTypes();
	}

	/**
	 * The narrowing a call's @phpstan-assert tags contribute - the new-world
	 * home of TypeSpecifier::specifyTypesFromAsserts(). Subjects and argument
	 * types are read through their ExpressionResults (stored, or priced once
	 * for out-of-frame asks), so createSubjectTypes() composes with the
	 * result-carried structure (impure gate, nullsafe twin) instead of
	 * create()'s scope re-probing.
	 */
	public function specifyTypesFromAsserts(TypeSpecifierContext $context, CallLike $call, Assertions $assertions, ParametersAcceptor $parametersAcceptor, MutatingScope $scope): ?SpecifiedTypes
	{
		if ($context->null()) {
			$asserts = $assertions->getAsserts();
		} elseif ($context->true()) {
			$asserts = $assertions->getAssertsIfTrue();
		} elseif ($context->false()) {
			$asserts = $assertions->getAssertsIfFalse();
		} else {
			throw new ShouldNotHappenException();
		}

		if (count($asserts) === 0) {
			return null;
		}

		$argsMap = [];
		$parameters = $parametersAcceptor->getParameters();
		foreach ($call->getArgs() as $i => $arg) {
			if ($arg->unpack) {
				continue;
			}

			if ($arg->name !== null) {
				$paramName = $arg->name->toString();
			} elseif (isset($parameters[$i])) {
				$paramName = $parameters[$i]->getName();
			} elseif (count($parameters) > 0 && $parametersAcceptor->isVariadic()) {
				$lastParameter = array_last($parameters);
				$paramName = $lastParameter->getName();
			} else {
				continue;
			}

			$argsMap[$paramName][] = $arg->value;
		}
		foreach ($parameters as $parameter) {
			$name = $parameter->getName();
			$defaultValue = $parameter->getDefaultValue();
			if (isset($argsMap[$name]) || $defaultValue === null) {
				continue;
			}
			$argsMap[$name][] = new TypeExpr($defaultValue);
		}

		if ($call instanceof MethodCall) {
			$argsMap['this'] = [$call->var];
		}

		$getArgType = static function (Expr $expr) use ($scope): Type {
			if ($expr instanceof TypeExpr) {
				return $expr->getExprType();
			}

			$storage = $scope->getCurrentExpressionResultStorage();
			$result = $storage !== null ? $storage->findExpressionResult($expr) : null;
			if ($result !== null) {
				return $result->getTypeOnScope($scope, $scope->nativeTypesPromoted);
			}

			return $scope->getStateType($expr);
		};

		/** @var SpecifiedTypes|null $types */
		$types = null;

		foreach ($asserts as $assert) {
			foreach ($argsMap[substr($assert->getParameter()->getParameterName(), 1)] ?? [] as $parameterExpr) {
				$assertedType = TypeTraverser::map($assert->getType(), static function (Type $type, callable $traverse) use ($argsMap, $getArgType): Type {
					if ($type instanceof ConditionalTypeForParameter) {
						$parameterName = substr($type->getParameterName(), 1);
						if (array_key_exists($parameterName, $argsMap)) {
							$type = $traverse($type);
							if ($type instanceof ConditionalTypeForParameter) {
								$argType = TypeCombinator::union(...array_map($getArgType, $argsMap[substr($type->getParameterName(), 1)]));
								return $type->toConditional($argType);
							}
							return $type;
						}
					}

					return $traverse($type);
				});

				$assertExpr = $assert->getParameter()->getExpr($parameterExpr);

				$templateTypeMap = $parametersAcceptor->getResolvedTemplateTypeMap();
				$containsUnresolvedTemplate = false;
				TypeTraverser::map(
					$assert->getOriginalType(),
					static function (Type $type, callable $traverse) use ($templateTypeMap, &$containsUnresolvedTemplate) {
						if ($type instanceof TemplateType && $type->getScope()->getClassName() !== null) {
							$resolvedType = $templateTypeMap->getType($type->getName());
							if ($resolvedType === null || $type->getBound()->equals($resolvedType)) {
								$containsUnresolvedTemplate = true;
								return $type;
							}
						}

						return $traverse($type);
					},
				);

				$assertStorage = $scope->getCurrentExpressionResultStorage();
				$subjectResult = $assertExpr instanceof TypeExpr || $assertStorage === null
					? null
					: $assertStorage->findExpressionResult($assertExpr);
				if ($subjectResult === null && $assertExpr instanceof CallLike && !$this->mayRememberCallSubject($scope, $assertExpr)) {
					// a call subject whose value must not be remembered (side
					// effects) contributes no narrowing - old create()'s purity
					// gate, derived from reflection instead of a walk
					continue;
				}
				$newTypes = $this->createSubjectTypes(
					$scope,
					$assertExpr,
					$subjectResult,
					$assertedType,
					$assert->isNegated() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createTrue(),
				)->setRootExpr($containsUnresolvedTemplate || $assert->isEquality() ? $call : null);
				$types = $types !== null ? $types->unionWith($newTypes) : $newTypes;

				if (!$context->null() || (!$assertedType->isTrue()->yes() && !$assertedType->isFalse()->yes())) {
					continue;
				}

				$subContext = $assertedType->isTrue()->yes() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();
				if ($assert->isNegated()) {
					$subContext = $subContext->negate();
				}

				$types = $types->unionWith($this->specifyTypesForNode(
					$scope,
					$assertExpr,
					$subContext,
				));
			}
		}

		return $types;
	}

	/**
	 * The narrowing a conditional return type (`($x is Foo ? true : false)`)
	 * contributes to its argument - the new-world home of
	 * TypeSpecifier::specifyTypesFromConditionalReturnType(). The argument's
	 * narrowing composes through its ExpressionResult.
	 */
	public function specifyTypesFromConditionalReturnType(
		TypeSpecifierContext $context,
		Expr\CallLike $call,
		ParametersAcceptor $parametersAcceptor,
		MutatingScope $scope,
	): ?SpecifiedTypes
	{
		if (!$parametersAcceptor instanceof ResolvedFunctionVariant) {
			return null;
		}

		$returnType = $parametersAcceptor->getOriginalParametersAcceptor()->getReturnType();
		if (!$returnType instanceof ConditionalTypeForParameter) {
			return null;
		}

		if ($context->true()) {
			$leftType = new ConstantBooleanType(true);
			$rightType = new ConstantBooleanType(false);
		} elseif ($context->false()) {
			$leftType = new ConstantBooleanType(false);
			$rightType = new ConstantBooleanType(true);
		} elseif ($context->null()) {
			$leftType = new MixedType();
			$rightType = new NeverType();
		} else {
			return null;
		}

		$argumentExpr = null;
		$parameters = $parametersAcceptor->getParameters();
		foreach ($call->getArgs() as $i => $arg) {
			if ($arg->unpack) {
				continue;
			}

			if ($arg->name !== null) {
				$paramName = $arg->name->toString();
			} elseif (isset($parameters[$i])) {
				$paramName = $parameters[$i]->getName();
			} else {
				continue;
			}

			if ($returnType->getParameterName() !== '$' . $paramName) {
				continue;
			}

			$argumentExpr = $arg->value;
		}

		if ($argumentExpr === null) {
			return null;
		}

		return $this->getConditionalSpecifiedTypes($returnType, $leftType, $rightType, $scope, $argumentExpr);
	}

	private function getConditionalSpecifiedTypes(
		ConditionalTypeForParameter $conditionalType,
		Type $leftType,
		Type $rightType,
		MutatingScope $scope,
		Expr $argumentExpr,
	): ?SpecifiedTypes
	{
		$targetType = $conditionalType->getTarget();
		$ifType = $conditionalType->getIf();
		$elseType = $conditionalType->getElse();

		if (
			(
				$argumentExpr instanceof Node\Scalar
				|| ($argumentExpr instanceof ConstFetch && in_array(strtolower($argumentExpr->name->toString()), ['true', 'false', 'null'], true))
			) && ($ifType instanceof NeverType || $elseType instanceof NeverType)
		) {
			return null;
		}

		if ($leftType->isSuperTypeOf($ifType)->yes() && $rightType->isSuperTypeOf($elseType)->yes()) {
			$context = $conditionalType->isNegated() ? TypeSpecifierContext::createFalse() : TypeSpecifierContext::createTrue();
		} elseif ($leftType->isSuperTypeOf($elseType)->yes() && $rightType->isSuperTypeOf($ifType)->yes()) {
			$context = $conditionalType->isNegated() ? TypeSpecifierContext::createTrue() : TypeSpecifierContext::createFalse();
		} else {
			return null;
		}

		$argumentStorage = $scope->getCurrentExpressionResultStorage();
		$argumentResult = $argumentStorage !== null ? $argumentStorage->findExpressionResult($argumentExpr) : null;
		if ($argumentResult === null && $argumentExpr instanceof CallLike && !$this->mayRememberCallSubject($scope, $argumentExpr)) {
			// old create()'s purity gate, derived from reflection instead of a walk
			return null;
		}
		$specifiedTypes = $this->createSubjectTypes(
			$scope,
			$argumentExpr,
			$argumentResult,
			$targetType,
			$context,
		);

		if ($targetType->isTrue()->yes() || $targetType->isFalse()->yes()) {
			if ($targetType->isFalse()->yes()) {
				$context = $context->negate();
			}

			$specifiedTypes = $specifiedTypes->unionWith($this->specifyTypesForNode($scope, $argumentExpr, $context));
		}

		return $specifiedTypes;
	}

	/**
	 * Whether a call subject's value may be remembered by narrowing - the
	 * walk-free equivalent of the impure gate a stored ExpressionResult
	 * carries: a call with (possible) side effects yields a different value
	 * next time, so pinning a type to its expression string would lie.
	 */
	private function mayRememberCallSubject(MutatingScope $scope, Expr $expr): bool
	{
		if ($expr instanceof Expr\FuncCall && $expr->name instanceof Name) {
			if (!$this->reflectionProvider->hasFunction($expr->name, $scope)) {
				return false;
			}
			$hasSideEffects = $this->reflectionProvider->getFunction($expr->name, $scope)->hasSideEffects();
		} elseif ($expr instanceof Expr\MethodCall && $expr->name instanceof Identifier) {
			$methodReflection = $scope->getMethodReflection($scope->getStateType($expr->var), $expr->name->toString());
			if ($methodReflection === null) {
				return false;
			}
			$hasSideEffects = $methodReflection->hasSideEffects();
		} elseif ($expr instanceof Expr\StaticCall && $expr->name instanceof Identifier && $expr->class instanceof Name) {
			$methodReflection = $scope->getMethodReflection($scope->resolveTypeByName($expr->class), $expr->name->toString());
			if ($methodReflection === null) {
				return false;
			}
			$hasSideEffects = $methodReflection->hasSideEffects();
		} else {
			return false;
		}

		if ($hasSideEffects->yes()) {
			return false;
		}

		return $this->rememberPossiblyImpureFunctionValues || $hasSideEffects->no();
	}

}
