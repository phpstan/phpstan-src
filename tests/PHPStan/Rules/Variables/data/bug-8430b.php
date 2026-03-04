<?php

declare(strict_types=1);

namespace Bug8430b;

class A
{
	/** @var A[][] */
	public array $a;
	/** @var A[] */
	public array $b;
	/** @var array<string,string|string[]> */
	public array $c;
	public string $d;
}

class B
{
	private function abc(): void
	{
	}

	/**
	 * @param A[] $a
	 * @param array<string,string> $b
	 */
	public function def(array $a, array $b, string $c, bool $d): void
	{
		$e = false;
		$f = false;
		switch ($b['repeat'] ?? null) {
			case 'Y':
				$e = true;
				break;
			case 'A':
				$e = true;
				$f = true;
				break;
		}
		$g = 5;
		$h = 0;
		for ($i = 1; $i <= $g; $i++) {
			if (!$d) {
				$arr = ['a' => 1];
			}
			$j = $a[$i] ?? null;
			if ($j) {
				/** @var array<A[]> $k */
				$k = [];
				if ($e) {
					foreach ($j->a as $l) {
						/** @var A[] $m */
						$m = $f || empty($k) ? $j->b : [];
						array_push($m, ...$l);
						array_push($k, $m);
					}
					if (empty($k)) {
						array_push($k, $j->b);
					}
				} else {
					array_push($k, $j->b);
					foreach ($j->a as $l) {
						array_push($k[0], ...$l);
						break;
					}
				}
				foreach ($k as $n) {
					if (!$d) {
						foreach ($n as $o) {
							switch ($o->c['x'] ?? '') {
								case 'y':
									$p = $o->c[$o->d] ?? null;
									if (is_array($p)) {
										$this->abc();
									}
									break;
								case 'z':
									$p = $o->c[$o->d] ?? null;
									if (is_array($p)) {
										$this->abc();
									}
									break;
								default:
									$this->abc();
									break;
							}
						}
					}
					if (!empty($n)) {
						$h++;
					}
				}
			}
			if (!$c && !$d) {
				echo $arr['a'];
			}
		}
	}

	public function ghi(string $a, bool $b): void
	{
		if (!$b) {
			$arr = ['a' => 1];
		}
		$this->abc();
		if (!$a && !$b) {
			echo $arr['a'];
		}
	}
}
