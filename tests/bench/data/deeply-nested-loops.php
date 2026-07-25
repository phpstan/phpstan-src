<?php declare(strict_types = 1);

namespace BenchDeeplyNestedLoops;

class Foo
{
	/**
	 * @param list<string> $items
	 */
	public function fiveLevelWhile(array $items): int
	{
		$count = 0;
		while ($items !== []) {
			$item = array_shift($items);
			$parts = explode('.', $item);
			while ($parts !== []) {
				$part = array_shift($parts);
				$chars = str_split($part);
				while ($chars !== []) {
					$char = array_shift($chars);
					$codes = [ord($char)];
					while ($codes !== []) {
						$code = array_shift($codes);
						$bits = [];
						while ($code > 0) {
							$bits[] = $code & 1;
							$code >>= 1;
						}
						$count += count($bits);
					}
				}
			}
		}
		return $count;
	}

	/**
	 * @param list<string> $items
	 */
	public function fiveLevelFor(array $items): int
	{
		$count = 0;
		for ($a = 0; $a < count($items); $a++) {
			$parts = explode('.', $items[$a]);
			for ($b = 0; $b < count($parts); $b++) {
				$chars = str_split($parts[$b]);
				for ($c = 0; $c < count($chars); $c++) {
					$codes = [ord($chars[$c])];
					for ($d = 0; $d < count($codes); $d++) {
						$code = $codes[$d];
						for ($e = 0; $e < 8; $e++) {
							$count += ($code >> $e) & 1;
						}
					}
				}
			}
		}
		return $count;
	}

	/**
	 * @param list<string> $items
	 */
	public function fiveLevelForeach(array $items): int
	{
		$count = 0;
		foreach ($items as $item) {
			$parts = explode('.', $item);
			foreach ($parts as $part) {
				$chars = str_split($part);
				foreach ($chars as $char) {
					$codes = [ord($char)];
					foreach ($codes as $code) {
						$bits = [];
						foreach (range(0, 7) as $i) {
							$bits[] = ($code >> $i) & 1;
						}
						$count += array_sum($bits);
					}
				}
			}
		}
		return $count;
	}

	/**
	 * @param array<string, list<int>> $data
	 */
	public function mixedLoopTypes(array $data): int
	{
		$total = 0;
		foreach ($data as $key => $values) {
			$i = 0;
			while ($i < count($values)) {
				$n = $values[$i];
				for ($j = 0; $j < $n; $j++) {
					$k = $j;
					do {
						$total += $k;
						$k--;
					} while ($k > 0);
				}
				$i++;
			}
		}
		return $total;
	}
}
