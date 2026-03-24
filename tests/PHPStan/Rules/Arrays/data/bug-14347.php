<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14347;

enum Suit: string {
	case Clubs = 'c';
	case Diamonds = 'd';
	case Hearts = 'h';
	case Spades = 's';
}
/**
 * @param array<array-key, Suit> $cards
 * @return array<non-empty-string, non-negative-int>
 */
function countCards(array $cards): array {
	$cardCounts = ['all' => 0];
	foreach ($cards as $card) {
		$cardCounts['all']++;
		$cardCounts[$card->value] ??= 0;
		$cardCounts[$card->value]++;
	}
	return $cardCounts;
}
/**
 * @param array<array-key, Suit> $cards
 * @return array<non-empty-string, non-negative-int>
 */
function countCardsBroken(array $cards): array {
	$cardCounts = ['all' => 0];
	foreach ($cards as $card) {
		$cardCounts[$card->value] ??= 0;
		$cardCounts['all']++;
		$cardCounts[$card->value]++;
	}
	return $cardCounts;
}
