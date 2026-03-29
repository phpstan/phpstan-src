<?php declare(strict_types = 1);

namespace Bug14394;

class Cl {
	/** @param list<mixed> $v2 */
	public static function test(float $v1, array $v2): void {
		if ($v1 == NAN) { echo "never reached\n"; }
		if ($v1 === NAN) { echo "never reached\n"; }
		if ($v2 == [NAN]) { echo "never reached\n"; }
		if ($v2 === [NAN]) { echo "never reached\n"; }
	}
}
