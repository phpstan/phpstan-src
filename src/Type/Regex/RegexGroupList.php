<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use function array_reverse;
use function count;

/**
 * @implements IteratorAggregate<int, RegexCapturingGroup>
 */
final class RegexGroupList implements Countable, IteratorAggregate
{

	/**
	 * @param array<int, RegexCapturingGroup> $groups
	 */
	public function __construct(
		private readonly array $groups,
	)
	{
	}

	public function countTrailingOptionals(): int
	{
		$trailingOptionals = 0;
		foreach (array_reverse($this->groups) as $captureGroup) {
			if (!$captureGroup->isOptional()) {
				break;
			}
			$trailingOptionals++;
		}
		return $trailingOptionals;
	}

	public function forceGroupNonOptional(RegexCapturingGroup $group): self
	{
		return $this->cloneAndReParentList($group);
	}

	public function forceGroupTypeAndNonOptional(RegexCapturingGroup $group, Type $type): self
	{
		return $this->cloneAndReParentList($group, $type);
	}

	private function cloneAndReParentList(RegexCapturingGroup $target, ?Type $type = null): self
	{
		$groups = [];
		$forcedGroup = null;
		foreach ($this->groups as $i => $group) {
			if ($group->getId() === $target->getId()) {
				$forcedGroup = $group->forceNonOptional();
				if ($type !== null) {
					$forcedGroup = $forcedGroup->forceType($type);
				}
				$groups[$i] = $forcedGroup;

				continue;
			}

			$groups[$i] = $group;
		}

		if ($forcedGroup === null) {
			throw new ShouldNotHappenException();
		}

		foreach ($groups as $i => $group) {
			$parent = $group->getParent();

			while ($parent !== null) {
				if ($parent instanceof RegexNonCapturingGroup) {
					$parent = $parent->getParent();
					continue;
				}

				if ($parent->getId() === $target->getId()) {
					$groups[$i] = $groups[$i]->withParent($forcedGroup);
				}
				$parent = $parent->getParent();
			}
		}

		return new self($groups);
	}

	public function removeGroup(RegexCapturingGroup $remove): self
	{
		$groups = [];
		foreach ($this->groups as $i => $group) {
			if ($group->getId() === $remove->getId()) {
				continue;
			}

			$groups[$i] = $group;
		}

		return new self($groups);
	}

	public function getOnlyOptionalTopLevelGroup(): ?RegexCapturingGroup
	{
		$group = null;
		foreach ($this->groups as $captureGroup) {
			if (!$captureGroup->isTopLevel()) {
				continue;
			}

			if (!$captureGroup->isOptional()) {
				return null;
			}

			if ($group !== null) {
				return null;
			}

			$group = $captureGroup;
		}

		return $group;
	}

	public function getOnlyTopLevelAlternation(): ?RegexAlternation
	{
		$alternation = null;
		foreach ($this->groups as $captureGroup) {
			if (!$captureGroup->isTopLevel()) {
				continue;
			}

			if (!$captureGroup->inAlternation()) {
				return null;
			}

			if ($captureGroup->inOptionalQuantification()) {
				return null;
			}

			if ($alternation === null) {
				$alternation = $captureGroup->getAlternation();
			} elseif ($alternation->getId() !== $captureGroup->getAlternation()->getId()) {
				return null;
			}
		}

		return $alternation;
	}

	public function count(): int
	{
		return count($this->groups);
	}

	/**
	 * @return ArrayIterator<int, RegexCapturingGroup>
	 */
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->groups);
	}

}
