<?php declare(strict_types = 1);

namespace Bug12653;

class Reproduction
{
	const TYPE_XXX = 'xxx';
	const TYPE_YYY = 'yyy';
	const TYPE_ZZZ = 'zzz';

	/**
	 * @return array<'a'|'b'|'c'|'d',Reproduction::TYPE_*>
	 */
	public function main()
	{
		$list = [
			'a' => Reproduction::TYPE_XXX,
			'b' => Reproduction::TYPE_YYY,
			'c' => Reproduction::TYPE_ZZZ,
			'd' => Reproduction::TYPE_XXX,
		];

		$keys = ['a', 'b', 'c', 'd'];
		$found = false;
		foreach ($keys as $key) {
			if ($list[$key] === Reproduction::TYPE_XXX) {
				// The first matched key is kept and subsequent matched keys are rewritten.
				if (!$found) {
					$found = true;
				} else {
					$list[$key] = Reproduction::TYPE_ZZZ;
				}
			}
		}

		return $list;
	}
}
