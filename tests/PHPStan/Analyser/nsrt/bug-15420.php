<?php declare(strict_types = 1);

namespace Bug15420;

use function PHPStan\Testing\assertType;

class Test {
	/** @param string[] $cards */
	public function f(array $cards): void
	{
	    for ($i = 0; $i < 10; $i++) {
	        $seen = [];
	        $later = [];
	        foreach ($cards as $card) {
	            if (array_key_exists($card, $seen)) {
	                $later[] = $card;
	                continue;
	            }
	            $seen[$card] = true;
	        }
	        assertType('int<0, 9>', $i);
	        if ($later !== []) {
	            $cards = $later;
	        } else {
	            break;
	        }
	    }
	}
}
