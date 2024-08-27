<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;

final class SpecifiedTypes
{

	/**
	 * @api
	 * @param array<string, array{Expr, Type}> $sureTypes
	 * @param array<string, array{Expr, Type}> $sureNotTypes
	 * @param array<string, ConditionalExpressionHolder[]> $newConditionalExpressionHolders
	 */
	public function __construct(
		private array $sureTypes = [],
		private array $sureNotTypes = [],
		private bool $overwrite = false,
		private array $newConditionalExpressionHolders = [],
		private ?Expr $rootExpr = null,
	)
	{
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

		return new self($sureTypeUnion, $sureNotTypeUnion, $this->overwrite && $other->overwrite, [], $rootExpr);
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

		return new self($sureTypeUnion, $sureNotTypeUnion, $this->overwrite || $other->overwrite, [], $rootExpr);
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

		return new self($sureTypes, [], $this->overwrite, $this->newConditionalExpressionHolders, $this->rootExpr);
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
