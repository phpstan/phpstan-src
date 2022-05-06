<?php // lint >= 7.4

namespace Bug6023;

class Grouper
{
	/**
	 * @param array{commissions: array<Clearable>, leftovers: array<Clearable>} $groups
	 * @return array{commissions: array<Clearable>, leftovers: array<Clearable>}
	 */
	public function groupByType(array $groups, Clearable $clearable): array
	{
		$group = $clearable->type === 'foo' ? 'commissions' : 'leftovers';
		$groups[$group][] = $clearable;
		return $groups;
	}
}

class Clearable
{
	public string $type = 'foo';
}
