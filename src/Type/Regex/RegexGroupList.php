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

	public function forceGroupIdNonOptional(int $id): self
	{
		return $this->cloneAndReParentList($id);
	}

	public function forceGroupIdTypeAndNonOptional(int $id, Type $type): self
	{
		return $this->cloneAndReParentList($id, $type);
	}

	private function cloneAndReParentList(int $id, ?Type $type = null): self
	{
		$groups = [];
		$forcedGroup = null;
		foreach ($this->groups as $i => $group) {
			if ($group->getId() === $id) {
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

				if ($parent->getId() === $id) {
					$groups[$i] = $groups[$i]->withParent($forcedGroup);
				}
				$parent = $parent->getParent();
			}
		}

		return new self($groups);
	}

	public function removeGroup(int $id): self
	{
		$groups = [];
		foreach ($this->groups as $i => $group) {
			if ($group->getId() === $id) {
				continue;
			}

			$groups[$i] = $group;
		}

		return new self($groups);
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
