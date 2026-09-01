<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\UnresolvedTemplateArgumentType;
use PHPStan\Type\Type;
use function array_pop;
use function spl_object_id;

/**
 * Persistent collection of inference facts. Branches share their prefix; appending
 * and joining take constant time. Only the resolver flattens the collection.
 * No fact retains a scope, expression result, or mutable collection context.
 */
final class TemplateArgumentConstraints
{

	/** @param array{UnresolvedTemplateArgumentType, Type|null, TemplateTypeVariance|null, bool}|null $fact */
	private function __construct(
		private readonly ?self $left = null,
		private readonly ?self $right = null,
		private readonly ?array $fact = null,
	)
	{
	}

	public static function createEmpty(): self
	{
		return new self();
	}

	public function isEmpty(): bool
	{
		return $this->left === null && $this->right === null && $this->fact === null;
	}

	public function merge(self $other): self
	{
		if ($this === $other || $other->isEmpty()) {
			return $this;
		}
		if ($this->isEmpty()) {
			return $other;
		}

		return new self($this, $other);
	}

	public function withSite(UnresolvedTemplateArgumentType $marker): self
	{
		if ($marker->getSite()->getAttribute(TemplateArgumentFrame::SYNTHETIC_SITE_ATTRIBUTE) === true) {
			return $this;
		}

		return new self($this, fact: [$marker, null, null, false]);
	}

	public function withSend(UnresolvedTemplateArgumentType $marker, Type $type, TemplateTypeVariance $variance): self
	{
		return new self($this, fact: [$marker, $type, $variance, false]);
	}

	public function withLowerBound(UnresolvedTemplateArgumentType $marker, Type $type): self
	{
		return new self($this, fact: [$marker, $type, null, false]);
	}

	public function withUnconstrainingSend(UnresolvedTemplateArgumentType $marker): self
	{
		return new self($this, fact: [$marker, null, null, true]);
	}

	/** @return iterable<array{UnresolvedTemplateArgumentType, Type|null, TemplateTypeVariance|null, bool}> */
	public function getFacts(): iterable
	{
		$stack = [[$this, false]];
		$visited = [];
		while ($stack !== []) {
			[$current, $expanded] = array_pop($stack);
			if ($expanded) {
				if ($current->fact !== null) {
					yield $current->fact;
				}
				continue;
			}
			$id = spl_object_id($current);
			if (isset($visited[$id])) {
				continue;
			}
			$visited[$id] = true;
			$stack[] = [$current, true];
			if ($current->right !== null) {
				$stack[] = [$current->right, false];
			}
			if ($current->left === null) {
				continue;
			}

			$stack[] = [$current->left, false];
		}
	}

}
