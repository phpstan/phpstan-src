<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ArrayDimFetch;
use PHPStan\Node\Expr\ExistingArrayDimFetch;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;

/**
 * The pre-value half of an assignment, produced by AssignHandler::prepareTarget():
 * the target's sub-expressions (root, dimensions, receiver, dynamic name) are
 * walked in PHP's evaluation order and everything AssignHandler::applyWrite()
 * needs to perform the write is captured here. The caller processes the assigned
 * value on getScope() between the two calls - inline, instead of through an
 * injected callback.
 *
 * @internal
 */
final class PreparedAssignTarget
{

	public const KIND_VARIABLE = 'variable';
	public const KIND_ARRAY_DIM_FETCH = 'arrayDimFetch';
	public const KIND_PROPERTY_FETCH = 'propertyFetch';
	public const KIND_STATIC_PROPERTY_FETCH = 'staticPropertyFetch';
	public const KIND_LIST = 'list';
	public const KIND_EXISTING_ARRAY_DIM_FETCH = 'existingArrayDimFetch';
	public const KIND_FALLBACK = 'fallback';

	/**
	 * @param self::KIND_* $kind
	 * @param InternalThrowPoint[] $throwPoints
	 * @param ImpurePoint[] $impurePoints
	 * @param non-empty-list<ArrayDimFetch>|null $dimFetchStack
	 * @param non-empty-list<array{Type|null, ArrayDimFetch}>|null $offsetTypes
	 * @param non-empty-list<array{Type|null, ArrayDimFetch}>|null $offsetNativeTypes
	 * @param non-empty-list<array{Type, ExistingArrayDimFetch}>|null $existingOffsetTypes
	 * @param non-empty-list<array{Type, ExistingArrayDimFetch}>|null $existingOffsetNativeTypes
	 * @param ExpressionResult[] $targetChainResults
	 */
	public function __construct(
		private string $kind,
		private Expr $var,
		private Expr $assignedExpr,
		private MutatingScope $beforeScope,
		private MutatingScope $scope,
		private bool $enterExpressionAssign,
		private bool $isAssignOp,
		private bool $hasYield,
		private array $throwPoints,
		private array $impurePoints,
		private bool $isAlwaysTerminating,
		private ?Expr $rootVar = null,
		private ?ExpressionResult $varResult = null,
		private ?array $dimFetchStack = null,
		private ?Expr $assignedPropertyExpr = null,
		private ?array $offsetTypes = null,
		private ?array $offsetNativeTypes = null,
		private ?array $existingOffsetTypes = null,
		private ?array $existingOffsetNativeTypes = null,
		private ?ExpressionResult $offsetSetTargetResult = null,
		private ?ExpressionResult $objectResult = null,
		private ?string $propertyName = null,
		private ?Type $propertyHolderType = null,
		private ?ExpressionResult $targetReadResult = null,
		private array $targetChainResults = [],
		private ?ExpressionResult $variableNameResult = null,
	)
	{
	}

	/**
	 * @return self::KIND_*
	 */
	public function getKind(): string
	{
		return $this->kind;
	}

	public function getVar(): Expr
	{
		return $this->var;
	}

	public function getAssignedExpr(): Expr
	{
		return $this->assignedExpr;
	}

	public function getBeforeScope(): MutatingScope
	{
		return $this->beforeScope;
	}

	/** The scope the caller processes the assigned value on. */
	public function getScope(): MutatingScope
	{
		return $this->scope;
	}

	public function enterExpressionAssign(): bool
	{
		return $this->enterExpressionAssign;
	}

	public function isAssignOp(): bool
	{
		return $this->isAssignOp;
	}

	public function hasYield(): bool
	{
		return $this->hasYield;
	}

	/**
	 * @return InternalThrowPoint[]
	 */
	public function getThrowPoints(): array
	{
		return $this->throwPoints;
	}

	/**
	 * @return ImpurePoint[]
	 */
	public function getImpurePoints(): array
	{
		return $this->impurePoints;
	}

	public function isAlwaysTerminating(): bool
	{
		return $this->isAlwaysTerminating;
	}

	public function getRootVar(): Expr
	{
		if ($this->rootVar === null) {
			throw new ShouldNotHappenException();
		}

		return $this->rootVar;
	}

	public function getVarResult(): ExpressionResult
	{
		if ($this->varResult === null) {
			throw new ShouldNotHappenException();
		}

		return $this->varResult;
	}

	/**
	 * @return non-empty-list<ArrayDimFetch>
	 */
	public function getDimFetchStack(): array
	{
		if ($this->dimFetchStack === null) {
			throw new ShouldNotHappenException();
		}

		return $this->dimFetchStack;
	}

	public function getAssignedPropertyExpr(): Expr
	{
		if ($this->assignedPropertyExpr === null) {
			throw new ShouldNotHappenException();
		}

		return $this->assignedPropertyExpr;
	}

	/**
	 * @return non-empty-list<array{Type|null, ArrayDimFetch}>
	 */
	public function getOffsetTypes(): array
	{
		if ($this->offsetTypes === null) {
			throw new ShouldNotHappenException();
		}

		return $this->offsetTypes;
	}

	/**
	 * @return non-empty-list<array{Type|null, ArrayDimFetch}>
	 */
	public function getOffsetNativeTypes(): array
	{
		if ($this->offsetNativeTypes === null) {
			throw new ShouldNotHappenException();
		}

		return $this->offsetNativeTypes;
	}

	/**
	 * @return non-empty-list<array{Type, ExistingArrayDimFetch}>
	 */
	public function getExistingOffsetTypes(): array
	{
		if ($this->existingOffsetTypes === null) {
			throw new ShouldNotHappenException();
		}

		return $this->existingOffsetTypes;
	}

	/**
	 * @return non-empty-list<array{Type, ExistingArrayDimFetch}>
	 */
	public function getExistingOffsetNativeTypes(): array
	{
		if ($this->existingOffsetNativeTypes === null) {
			throw new ShouldNotHappenException();
		}

		return $this->existingOffsetNativeTypes;
	}

	/**
	 * The chain link an ArrayAccess::offsetSet would be invoked on: the
	 * second-outermost link's write-flavoured result, or the root's result for
	 * a single-dimension target.
	 */
	public function getOffsetSetTargetResult(): ExpressionResult
	{
		if ($this->offsetSetTargetResult === null) {
			throw new ShouldNotHappenException();
		}

		return $this->offsetSetTargetResult;
	}

	public function getObjectResult(): ExpressionResult
	{
		if ($this->objectResult === null) {
			throw new ShouldNotHappenException();
		}

		return $this->objectResult;
	}

	public function getPropertyName(): ?string
	{
		return $this->propertyName;
	}

	public function getPropertyHolderType(): Type
	{
		if ($this->propertyHolderType === null) {
			throw new ShouldNotHappenException();
		}

		return $this->propertyHolderType;
	}

	/**
	 * The whole target priced as a read - produced only in the
	 * read-modify-write walk modes.
	 */
	public function getTargetReadResult(): ExpressionResult
	{
		if ($this->targetReadResult === null) {
			throw new ShouldNotHappenException();
		}

		return $this->targetReadResult;
	}

	/**
	 * @return ExpressionResult[]
	 */
	public function getTargetChainResults(): array
	{
		return $this->targetChainResults;
	}

	/**
	 * A dynamic variable name (`$$name`) already walked by prepareTarget() -
	 * read-modify-write targets evaluate the name before reading the old value.
	 */
	public function getVariableNameResult(): ?ExpressionResult
	{
		return $this->variableNameResult;
	}

}
