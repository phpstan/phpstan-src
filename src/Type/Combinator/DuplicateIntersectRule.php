<?php declare(strict_types = 1);

namespace PHPStan\Type\Combinator;

use function array_values;
use function count;
use function get_class;

/**
 * A member equal to another one adds nothing to an intersection: the repeats
 * of a member are folded into one before the rules that pair every member
 * with every other ask them how they relate. equals() is weaker than
 * interchangeability - `Foo=final` equals `Foo` - so each repeat meets the
 * one kept through the pairing rules, which keep the narrower of the two.
 *
 * @internal
 */
final class DuplicateIntersectRule implements IntersectRule
{

	/** @param list<IntersectRule> $pairRules */
	public function __construct(private array $pairRules)
	{
	}

	public function apply(array $types): array
	{
		$count = count($types);
		for ($i = 0; $i < $count; $i++) {
			for ($j = $i + 1; $j < $count; $j++) {
				if (get_class($types[$i]) !== get_class($types[$j]) || !$types[$i]->equals($types[$j])) {
					continue;
				}

				if ($types[$i] === $types[$j]) {
					unset($types[$j]);
					continue;
				}

				$pair = [$types[$i], $types[$j]];
				foreach ($this->pairRules as $rule) {
					$pair = $rule->apply($pair);
				}
				if (count($pair) !== 1) {
					continue;
				}

				$types[$i] = $pair[0];
				unset($types[$j]);
			}
			$types = array_values($types);
			$count = count($types);
		}

		return $types;
	}

}
