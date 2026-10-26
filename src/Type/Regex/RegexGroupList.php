<?php declare(strict_types = 1);

namespace PHPStan\Type\Regex;

use ArrayIterator;
use Countable;
use IteratorAggregate;
use Override;
use PHPStan\ShouldNotHappenException;
use PHPStan\Type\Type;
use function array_key_exists;
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

	public function forceGroupAlternationBranchMatched(RegexCapturingGroup $group): self
	{
		return $this->cloneAndReParentList($group, null, true);
	}

	private function cloneAndReParentList(RegexCapturingGroup $target, ?Type $type = null, bool $alternationBranchOnly = false): self
	{
		$groups = [];
		$forcedGroup = null;
		foreach ($this->groups as $i => $group) {
			if ($group->getId() === $target->getId()) {
				$forcedGroup = $alternationBranchOnly ? $group->forceAlternationBranchMatched() : $group->forceNonOptional();
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

	/**
	 * Groups sharing a name (allowed by (?J)) are only understood
	 * when each of them is in a different branch of the given alternation.
	 */
	public function hasDuplicateNamesOutsideOfAlternationBranches(?RegexAlternation $alternation): bool
	{
		$groupIdsByName = [];
		foreach ($this->groups as $captureGroup) {
			if (!$captureGroup->isNamed()) {
				continue;
			}

			$groupIdsByName[$captureGroup->getName()][] = $captureGroup->getId();
		}

		$branchByGroupId = [];
		if ($alternation !== null) {
			foreach ($alternation->getGroupCombinations() as $branch => $groupIds) {
				foreach ($groupIds as $groupId) {
					$branchByGroupId[$groupId] = $branch;
				}
			}
		}

		foreach ($groupIdsByName as $groupIds) {
			if (count($groupIds) === 1) {
				continue;
			}

			$branches = [];
			foreach ($groupIds as $groupId) {
				if (!array_key_exists($groupId, $branchByGroupId)) {
					return true;
				}

				$branch = $branchByGroupId[$groupId];
				if (array_key_exists($branch, $branches)) {
					return true;
				}

				$branches[$branch] = true;
			}
		}

		return false;
	}

	public function hasGroupResettingGroupCounter(): bool
	{
		foreach ($this->groups as $captureGroup) {
			if ($captureGroup->resetsGroupCounter()) {
				return true;
			}
		}

		return false;
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

	#[Override]
	public function count(): int
	{
		return count($this->groups);
	}

	/**
	 * @return ArrayIterator<int, RegexCapturingGroup>
	 */
	#[Override]
	public function getIterator(): ArrayIterator
	{
		return new ArrayIterator($this->groups);
	}

}
