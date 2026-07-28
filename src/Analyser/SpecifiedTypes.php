<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_key_exists;
use function array_merge;

final class SpecifiedTypes
{

	private bool $overwrite = false;

	private bool $equality = false;

	/** @var array<string, ConditionalExpressionHolder[]> */
	private array $newConditionalExpressionHolders = [];

	private ?Expr $rootExpr = null;

	/**
	 * @api
	 * @param array<string, array{Expr, Type}> $sureTypes
	 * @param array<string, array{Expr, Type}> $sureNotTypes
	 */
	public function __construct(
		private array $sureTypes = [],
		private array $sureNotTypes = [],
	)
	{
	}

	/**
	 * Normally, $sureTypes in truthy context are used to intersect with the pre-existing type.
	 * And $sureNotTypes are used to remove type from the pre-existing type.
	 *
	 * Example: By default, non-empty-string intersected with '' (ConstantStringType) will lead to NeverType.
	 * Because it's not possible to narrow non-empty-string to an empty string.
	 *
	 * In rare cases, a type-specifying extension might want to overwrite the pre-existing types
	 * without taking the pre-existing types into consideration.
	 *
	 * In that case it should also call setAlwaysOverwriteTypes() on
	 * the returned object.
	 *
	 * ! Only do this if you're certain. Otherwise, this is a source of common bugs. !
	 *
	 * @api
	 */
	public function setAlwaysOverwriteTypes(): self
	{
		$self = new self($this->sureTypes, $this->sureNotTypes);
		$self->overwrite = true;
		$self->equality = $this->equality;
		$self->newConditionalExpressionHolders = $this->newConditionalExpressionHolders;
		$self->rootExpr = $this->rootExpr;

		return $self;
	}

	/**
	 * Marks these types as coming from an equality check, the same concept as
	 * the "=Type" equality assertions documented at
	 * https://phpstan.org/writing-php-code/narrowing-types#equality-assertions
	 *
	 * The narrowed types are only applied; they do not determine the check
	 * outcome, so ImpossibleCheckTypeHelper will not use them to report
	 * always-true/false for the check expression.
	 *
	 * @api
	 */
	public function setEquality(): self
	{
		$self = new self($this->sureTypes, $this->sureNotTypes);
		$self->overwrite = $this->overwrite;
		$self->equality = true;
		$self->newConditionalExpressionHolders = $this->newConditionalExpressionHolders;
		$self->rootExpr = $this->rootExpr;

		return $self;
	}

	public function isEquality(): bool
	{
		return $this->equality;
	}

	/**
	 * @api
	 */
	public function setRootExpr(?Expr $rootExpr): self
	{
		$self = new self($this->sureTypes, $this->sureNotTypes);
		$self->overwrite = $this->overwrite;
		$self->equality = $this->equality;
		$self->newConditionalExpressionHolders = $this->newConditionalExpressionHolders;
		$self->rootExpr = $rootExpr;

		return $self;
	}

	/**
	 * @param array<string, ConditionalExpressionHolder[]> $newConditionalExpressionHolders
	 */
	public function setNewConditionalExpressionHolders(array $newConditionalExpressionHolders): self
	{
		$self = new self($this->sureTypes, $this->sureNotTypes);
		$self->overwrite = $this->overwrite;
		$self->equality = $this->equality;
		$self->newConditionalExpressionHolders = $newConditionalExpressionHolders;
		$self->rootExpr = $this->rootExpr;

		return $self;
	}

	/**
	 * @api
	 * @return array<string, array{Expr, Type}>
	 */
	public function getSureTypes(): array
	{
		return $this->sureTypes;
	}

	/**
	 * @api
	 * @return array<string, array{Expr, Type}>
	 */
	public function getSureNotTypes(): array
	{
		return $this->sureNotTypes;
	}

	public function shouldOverwrite(): bool
	{
		return $this->overwrite;
	}

	/**
	 * @return array<string, ConditionalExpressionHolder[]>
	 */
	public function getNewConditionalExpressionHolders(): array
	{
		return $this->newConditionalExpressionHolders;
	}

	public function getRootExpr(): ?Expr
	{
		return $this->rootExpr;
	}

	public function removeExpr(string $exprString): self
	{
		$sureTypes = $this->sureTypes;
		$sureNotTypes = $this->sureNotTypes;
		unset($sureTypes[$exprString]);
		unset($sureNotTypes[$exprString]);

		$self = new self($sureTypes, $sureNotTypes);
		$self->overwrite = $this->overwrite;
		$self->equality = $this->equality;
		$self->newConditionalExpressionHolders = $this->newConditionalExpressionHolders;
		$self->rootExpr = $this->rootExpr;

		return $self;
	}

	/** @api */
	public function intersectWith(SpecifiedTypes $other): self
	{
		$sureTypeUnion = [];
		$sureNotTypeUnion = [];
		$rootExpr = $this->mergeRootExpr($this->rootExpr, $other->rootExpr);

		foreach ($this->sureTypes as $exprString => [$exprNode, $type]) {
			if (!isset($other->sureTypes[$exprString])) {
				continue;
			}

			$sureTypeUnion[$exprString] = [
				$exprNode,
				TypeCombinator::union($type, $other->sureTypes[$exprString][1]),
			];
		}

		foreach ($this->sureNotTypes as $exprString => [$exprNode, $type]) {
			if (!isset($other->sureNotTypes[$exprString])) {
				continue;
			}

			$sureNotTypeUnion[$exprString] = [
				$exprNode,
				TypeCombinator::intersect($type, $other->sureNotTypes[$exprString][1]),
			];
		}

		$result = new self($sureTypeUnion, $sureNotTypeUnion);
		if ($this->overwrite && $other->overwrite) {
			$result = $result->setAlwaysOverwriteTypes();
		}
		if ($this->equality || $other->equality) {
			$result->equality = true;
		}

		return $result->setRootExpr($rootExpr);
	}

	/** @api */
	public function unionWith(SpecifiedTypes $other): self
	{
		$sureTypeUnion = $this->sureTypes + $other->sureTypes;
		$sureNotTypeUnion = $this->sureNotTypes + $other->sureNotTypes;
		$rootExpr = $this->mergeRootExpr($this->rootExpr, $other->rootExpr);

		foreach ($this->sureTypes as $exprString => [$exprNode, $type]) {
			if (!isset($other->sureTypes[$exprString])) {
				continue;
			}

			$sureTypeUnion[$exprString] = [
				$exprNode,
				TypeCombinator::intersect($type, $other->sureTypes[$exprString][1]),
			];
		}

		foreach ($this->sureNotTypes as $exprString => [$exprNode, $type]) {
			if (!isset($other->sureNotTypes[$exprString])) {
				continue;
			}

			$sureNotTypeUnion[$exprString] = [
				$exprNode,
				TypeCombinator::union($type, $other->sureNotTypes[$exprString][1]),
			];
		}

		$result = new self($sureTypeUnion, $sureNotTypeUnion);
		if ($this->overwrite || $other->overwrite) {
			$result = $result->setAlwaysOverwriteTypes();
		}
		if ($this->equality || $other->equality) {
			$result->equality = true;
		}

		$conditionalExpressionHolders = $this->newConditionalExpressionHolders;
		foreach ($other->newConditionalExpressionHolders as $exprString => $holders) {
			if (!array_key_exists($exprString, $conditionalExpressionHolders)) {
				$conditionalExpressionHolders[$exprString] = $holders;
			} else {
				$conditionalExpressionHolders[$exprString] = array_merge($conditionalExpressionHolders[$exprString], $holders);
			}
		}
		$result->newConditionalExpressionHolders = $conditionalExpressionHolders;

		return $result->setRootExpr($rootExpr);
	}

	public function normalize(Scope $scope): self
	{
		$sureTypes = $this->sureTypes;

		foreach ($this->sureNotTypes as $exprString => [$exprNode, $sureNotType]) {
			if (!isset($sureTypes[$exprString])) {
				$sureTypes[$exprString] = [$exprNode, TypeCombinator::remove($scope->getType($exprNode), $sureNotType)];
				continue;
			}

			$sureTypes[$exprString][1] = TypeCombinator::remove($sureTypes[$exprString][1], $sureNotType);
		}

		$result = new self($sureTypes, []);
		if ($this->overwrite) {
			$result = $result->setAlwaysOverwriteTypes();
		}
		$result->equality = $this->equality;

		return $result->setRootExpr($this->rootExpr);
	}

	private function mergeRootExpr(?Expr $rootExprA, ?Expr $rootExprB): ?Expr
	{
		if ($rootExprA === $rootExprB) {
			return $rootExprA;
		}

		if ($rootExprA === null || $rootExprB === null) {
			return $rootExprA ?? $rootExprB;
		}

		return null;
	}

}
