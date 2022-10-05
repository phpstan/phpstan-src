<?php

namespace IntegerRangesAndConstants;

class HelloWorld
{
	/** @var 0|1|3 */
	public $i = 0;
	/** @var 0|1|2|3|string */
	public $j = 0;
	/** @var 0|1|bool|2|3 */
	public $k = 0;
	/** @var 0|1|2|3|4|5 */
	public $l;

	public function test(): void {
		$this->i = random_int(0, 3);
		$this->j = random_int(0, 3);
		$this->k = random_int(0, 3);
		$this->l = random_int(0, 3);
	}

	/**
	 * @var int<0,3>
	 */
	public $x = 0;

	/**
	 * @param 0| $a
	 * @param 0|1 $b
	 * @param 0|1|3 $c
	 * @param 0|1|2|3|string $j
	 * @param 0|1|bool|2|3 $k
	 * @param 0|1|bool|3 $l
	 * @param 0|1|3|4 $m
	 */
	public function test2($a, $b, $c, $j, $k, $l, $m): void {
		$this->x = $a;
		$this->x = $b;
		$this->x = $c;

		$this->x = $j;
		$this->x = $k;
		$this->x = $l;
		$this->x = $m;
	}

	const I_1=1;
	const I_2=2;

	/** @param int-mask<self::I_*> $flag */
	public function sayHello($flag): void
	{
		$this->x = $flag;
	}
}
