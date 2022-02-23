<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node\Expr;
use PhpParser\Node\Expr\ClassConstFetch;
use PhpParser\Node\Expr\ConstFetch;
use PhpParser\Node\Scalar;
use PHPStan\Type\MixedType;
use PHPStan\Type\SubtractableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_map;

class SpecifiedTypes
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

	/** @api */
	public function intersectWith(SpecifiedTypes $other): self
	{
		$normalized = $this->normalize();
		$otherNormalized = $other->normalize();

		$sureTypeUnion = [];
		$sureNotTypeUnion = [];

		foreach ($normalized->sureTypes as $exprString => [$exprNode, $type]) {
			if (!isset($otherNormalized->sureTypes[$exprString])) {
				continue;
			}

			$sureTypeUnion[$exprString] = [
				$exprNode,
				TypeCombinator::union($type, $otherNormalized->sureTypes[$exprString][1]),
			];
		}

		foreach ($normalized->sureNotTypes as $exprString => [$exprNode, $type]) {
			if (!isset($otherNormalized->sureNotTypes[$exprString])) {
				continue;
			}

			$sureNotTypeUnion[$exprString] = [
				$exprNode,
				TypeCombinator::intersect($type, $otherNormalized->sureNotTypes[$exprString][1]),
			];
		}

		return new self($sureTypeUnion, $sureNotTypeUnion);
	}

	/** @api */
	public function unionWith(SpecifiedTypes $other): self
	{
		$sureTypeUnion = $this->sureTypes + $other->sureTypes;
		$sureNotTypeUnion = $this->sureNotTypes + $other->sureNotTypes;

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

		return new self($sureTypeUnion, $sureNotTypeUnion);
	}

	public function inverse(): self
	{
		$normalized = $this->normalize();

		$inverseType = static function (Expr $expr, Type $type) {
			if ($expr instanceof ClassConstFetch || $expr instanceof ConstFetch || $expr instanceof Scalar) {
				return $type;
			}

			if ($type instanceof SubtractableType && $type->getSubtractedType() !== null) {
				return TypeCombinator::union($type->getTypeWithoutSubtractedType(), $type->getSubtractedType());
			}

			return new MixedType(false, $type);
		};

		return new self(
			array_map(static fn (array $sureType) => [$sureType[0], $inverseType($sureType[0], $sureType[1])], $normalized->sureTypes),
			array_map(static fn (array $sureNotType) => [$sureNotType[0], $inverseType($sureNotType[0], $sureNotType[1])], $normalized->sureNotTypes),
			$normalized->overwrite,
			$normalized->newConditionalExpressionHolders,
		);
	}

	private function normalize(): self
	{
		$sureTypes = $this->sureTypes;
		$sureNotTypes = [];

		foreach ($this->sureNotTypes as $exprString => [$exprNode, $sureNotType]) {
			if (!isset($sureTypes[$exprString])) {
				$sureNotTypes[$exprString] = [$exprNode, $sureNotType];
				continue;
			}

			$sureTypes[$exprString][1] = TypeCombinator::remove($sureTypes[$exprString][1], $sureNotType);
		}

		return new self($sureTypes, $sureNotTypes, $this->overwrite, $this->newConditionalExpressionHolders);
	}

}
