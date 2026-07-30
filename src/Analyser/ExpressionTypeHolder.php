<?php declare(strict_types = 1);

namespace PHPStan\Analyser;

use PhpParser\Node;
use PhpParser\Node\Expr;
use PHPStan\TrinaryLogic;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Turbo\ShadowedByTurboExtension;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use function array_pop;
use function get_class;
use function is_array;

#[ShadowedByTurboExtension(turboClass: 'PHPStanTurbo\ExpressionTypeHolder', implementation: __DIR__ . '/../../turbo-ext/src/ExpressionTypeHolder.cpp')]
#[ReferencedByTurboExtension(key: 'expressionTypeHolder')]
final class ExpressionTypeHolder
{

	/**
	 * The node key of every sub-expression, keyed to the classes it appears
	 * as - what MutatingScope::shouldInvalidateExpression()'s AST scan
	 * established per invalidation. Holders are shared across scope copies,
	 * so the one-time subtree scan amortizes over the many invalidation
	 * checks against the same holder.
	 *
	 * @var array<string, array<class-string<Expr>, true>>|null
	 */
	private ?array $containedNodeKeys = null;

	public function __construct(
		private readonly Expr $expr,
		private readonly Type $type,
		private readonly TrinaryLogic $certainty,
	)
	{
	}

	/**
	 * @param callable(Expr): string $keyBuilder
	 * @return array<string, array<class-string<Expr>, true>>
	 */
	public function getContainedNodeKeys(callable $keyBuilder): array
	{
		if ($this->containedNodeKeys !== null) {
			return $this->containedNodeKeys;
		}

		$keys = [];
		$stack = [$this->expr];
		while ($stack !== []) {
			$node = array_pop($stack);
			if ($node instanceof Expr) {
				$keys[$keyBuilder($node)][get_class($node)] = true;
			}
			foreach ($node->getSubNodeNames() as $subNodeName) {
				$subNode = $node->$subNodeName;
				if ($subNode instanceof Node) {
					$stack[] = $subNode;
				} elseif (is_array($subNode)) {
					foreach ($subNode as $subNodeItem) {
						if (!($subNodeItem instanceof Node)) {
							continue;
						}

						$stack[] = $subNodeItem;
					}
				}
			}
		}

		return $this->containedNodeKeys = $keys;
	}

	public static function createYes(Expr $expr, Type $type): self
	{
		return new self($expr, $type, TrinaryLogic::createYes());
	}

	public static function createMaybe(Expr $expr, Type $type): self
	{
		return new self($expr, $type, TrinaryLogic::createMaybe());
	}

	public function equalTypes(self $other): bool
	{
		if ($this === $other) {
			return true;
		}

		return $this->type === $other->type || $this->type->equals($other->type);
	}

	public function equals(self $other): bool
	{
		if ($this === $other) {
			return true;
		}

		if (!$this->certainty->equals($other->certainty)) {
			return false;
		}

		return $this->type === $other->type || $this->type->equals($other->type);
	}

	public function and(self $other): self
	{
		if ($this->type === $other->type || $this->type->equals($other->type)) {
			if ($this->certainty->and($other->certainty)->yes()) {
				return $this;
			}

			if ($this->certainty->maybe()) {
				return $this;
			}

			return $other;
		}

		return new self(
			$this->expr,
			TypeCombinator::union($this->type, $other->type),
			$this->certainty->and($other->certainty),
		);
	}

	public function getExpr(): Expr
	{
		return $this->expr;
	}

	public function getType(): Type
	{
		return $this->type;
	}

	public function getCertainty(): TrinaryLogic
	{
		return $this->certainty;
	}

}
