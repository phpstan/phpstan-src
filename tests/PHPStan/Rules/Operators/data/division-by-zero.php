<?php

class Foo
{
	/**
	 * @param negative-int $negative
	 * @param positive-int $positive
	 * @param int<-3,3> $both
	 * @param int<-3,-2>|int<0,2> $union
	 */
	public function test(
		int   $negative,
		int   $positive,
		int   $both,
		int   $union,
		int   $int,
		?int  $nullableInt,
		float $float,
		mixed $mixed
	) {
		42 / $negative;
		42 / $positive;
		42 / $both;
		42 / $union;
		42 / $int;
		42 / $nullableInt;
		42 / $float;
		42 / $mixed;

		if ($int !== 0) {
			42 / $int;
		}

		if ($nullableInt !== 0) {
			42 / $nullableInt;
			if ($nullableInt !== null) {
				42 / $nullableInt;
			}
		}

		42 % $negative;
		42 % $positive;
		42 % $both;
		42 % $union;
		42 % $int;
		42 % $nullableInt;
		42 % $float;
		42 % $mixed;

		if ($int !== 0) {
			42 % $int;
		}

		if ($nullableInt !== 0) {
			42 % $nullableInt;
			if ($nullableInt !== null) {
				42 % $nullableInt;
			}
		}
	}
}
