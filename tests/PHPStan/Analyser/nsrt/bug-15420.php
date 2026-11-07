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

/** @param string[] $cards */
function whileLoop(array $cards): void
{
	$i = 0;
	while ($i < 10) {
		$seen = [];
		$later = [];
		foreach ($cards as $card) {
			if (array_key_exists($card, $seen)) {
				$later[] = $card;
			}
			$seen[$card] = true;
		}
		if ($later === []) {
			break;
		}
		assertType('int<0, 9>', $i);
		$i++;
	}
}

/** @param string[] $cards */
function doWhileLoop(array $cards): void
{
	$i = 0;
	do {
		$seen = [];
		$later = [];
		foreach ($cards as $card) {
			if (array_key_exists($card, $seen)) {
				$later[] = $card;
			}
			$seen[$card] = true;
		}
		if ($later === []) {
			break;
		}
		assertType('int<0, max>', $i);
		$i++;
	} while ($i < 10);
}

/**
 * @param string[] $cards
 * @param int[] $rounds
 */
function foreachLoop(array $cards, array $rounds): void
{
	$i = 0;
	foreach ($rounds as $round) {
		$seen = [];
		$later = [];
		foreach ($cards as $card) {
			if (array_key_exists($card, $seen)) {
				$later[] = $card;
			}
			$seen[$card] = true;
		}
		if ($later === []) {
			break;
		}
		assertType('int<0, max>', $i);
		$i++;
	}
}

/**
 * @param string[] $cards
 * @param array{a: int, b: int, ...<string, int>} $rounds
 */
function unsealedConstantArrayForeachLoop(array $cards, array $rounds): void
{
	$i = 0;
	foreach ($rounds as $round) {
		$seen = [];
		$later = [];
		foreach ($cards as $card) {
			if (array_key_exists($card, $seen)) {
				$later[] = $card;
			}
			$seen[$card] = true;
		}
		if ($later === []) {
			break;
		}
		assertType('int<0, max>', $i);
		$i++;
	}
}

/** @param string[] $cards */
function innerWhile(array $cards): void
{
	for ($i = 0; $i < 10; $i++) {
		$seen = false;
		$later = false;
		$j = 0;
		while ($j < count($cards)) {
			if ($seen) {
				$later = true;
			}
			$seen = true;
			$j++;
		}
		if (!$later) {
			break;
		}
		assertType('int<0, 9>', $i);
	}
}

/** @param string[] $cards */
function innerFor(array $cards): void
{
	for ($i = 0; $i < 10; $i++) {
		$seen = false;
		$later = false;
		for ($j = 0; $j < count($cards); $j++) {
			if ($seen) {
				$later = true;
			}
			$seen = true;
		}
		if (!$later) {
			break;
		}
		assertType('int<0, 9>', $i);
	}
}

/** @param string[] $cards */
function innerDoWhile(array $cards): void
{
	for ($i = 0; $i < 10; $i++) {
		$seen = false;
		$later = false;
		$j = 0;
		do {
			if ($seen) {
				$later = true;
			}
			$seen = true;
			$j++;
		} while ($j < count($cards));
		if (!$later) {
			break;
		}
		assertType('int<0, 9>', $i);
	}
}
