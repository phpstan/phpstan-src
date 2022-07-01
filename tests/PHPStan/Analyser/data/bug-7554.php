<?php
namespace Bug7554;

class Readline
{

	/**
	 * Control-B binding.
	 * Move cursor backward one word.
	 * @param mixed $line
	 */
	public function _bindControlB($line, int $current): void
	{
		if (0 === $current) {
			return;
		}

		$words = \preg_split(
			'#\b#u',
			$line,
			-1,
			\PREG_SPLIT_OFFSET_CAPTURE | \PREG_SPLIT_NO_EMPTY
		);

		for (
			$i = 0, $max = \count($words) - 1;
			$i < $max && $words[$i + 1][1] < $current;
			++$i
		) {
		}
	}
}
