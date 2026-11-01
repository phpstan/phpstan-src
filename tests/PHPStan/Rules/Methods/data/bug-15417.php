<?php // lint >= 8.2

declare(strict_types = 1);

namespace Bug15417;

class Repro
{
	/** @return Collection<int, object{ch: string, x: Collection<int, object{t: string}>}> */
	public static function get(string $s): Collection
	{
		$data = [];
		foreach (explode(',', $s) as $char) {
			$c = array_map(fn($x) => (object)['t' => $x], ['a', 'b', 'c', 'd']);
			if ($s !== 'z') {
				$data[] = (object)['ch' => $char, 'x' => new Collection((new \Random\Randomizer)->shuffleArray($c))];
			}
		}

		return new Collection($data);
	}
}

/**
 * @template TKey of array-key
 * @template-covariant TValue (THEA fix by FixLarastanStubs.php)
 */
class Collection
{
	/** @param iterable<TKey, TValue> $a */
	public function __construct($a) { echo gettype($a); }
}

