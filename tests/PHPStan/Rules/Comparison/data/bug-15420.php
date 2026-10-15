<?php declare(strict_types = 1);

namespace Bug15420;

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
	        if ($later !== []) {
	            $cards = $later;
	        } else {
	            break;
	        }
	    }
	}
}
