<?php declare(strict_types = 1);

namespace LoopJoinConditionalExpressions;

use function PHPStan\Testing\assertType;

function foundFlagWhile(int $n): void
{
	$found = false;
	$val = null;
	while ($n-- > 0) {
		if (rand(0, 1) === 1) {
			$found = true;
			$val = 'x';
		}
	}
	if ($found) {
		assertType("'x'", $val);
	}
	assertType("'x'|null", $val);
}

/** @param list<string> $items */
function foundIndexForeachBreak(array $items): void
{
	$found = false;
	$idx = null;
	foreach ($items as $i => $item) {
		if ($item === 'a') {
			$found = true;
			$idx = $i;
			break;
		}
	}
	if ($found) {
		assertType('int<0, max>', $idx);
	}
}

function foundFlagForBreak(int $n): void
{
	$found = false;
	$val = null;
	for ($i = 0; $i < $n; $i++) {
		if (rand(0, 1) === 1) {
			$found = true;
			$val = 'x';
			break;
		}
	}
	if ($found) {
		assertType("'x'", $val);
	}
}

function foundFlagDoWhile(int $n): void
{
	$found = false;
	$val = null;
	do {
		if (rand(0, 1) === 1) {
			$found = true;
			$val = 'x';
		}
	} while ($n-- > 0);
	if ($found) {
		assertType("'x'", $val);
	}
}

function flagClearedLater(int $n): void
{
	$found = false;
	$val = null;
	while ($n-- > 0) {
		if (rand(0, 1) === 1) {
			$found = true;
			$val = 'x';
		} elseif (rand(0, 1) === 1) {
			$found = false;
		}
	}
	if ($found) {
		assertType("'x'", $val);
	} else {
		assertType("'x'|null", $val);
	}
}

function valueResetAfterFlag(int $n): void
{
	$found = false;
	$val = null;
	while ($n-- > 0) {
		if (rand(0, 1) === 1) {
			$found = true;
			$val = 'x';
		}
		if (rand(0, 1) === 1) {
			$val = null;
		}
	}
	if ($found) {
		assertType("'x'|null", $val);
	}
}

function flagAndValueIndependent(int $n): void
{
	$found = false;
	$val = null;
	while ($n-- > 0) {
		if (rand(0, 1) === 1) {
			$val = 'x';
		}
		if (rand(0, 1) === 1) {
			$found = true;
		}
	}
	if ($found) {
		assertType("'x'|null", $val);
	}
}

/** @param list<int> $items */
function valueResetOnContinuePath(array $items): void
{
	$found = false;
	$val = null;
	foreach ($items as $item) {
		if ($item > 5) {
			$found = true;
			$val = $item;
			continue;
		}
		$val = null;
	}
	if ($found) {
		assertType('int<6, max>|null', $val);
	}
}

/**
 * @param list<array{string, int}> $tokens
 * @return list<array{name: string, comment: string|null}>
 */
function implicationsFromNonConvergedLoop(array $tokens): array
{
	$identifiers = [];
	$comment = null;
	$openParenthesisCount = 0;
	$expected = [3];

	foreach ($tokens as [$content, $tokenType]) {
		if ($expected !== null && !in_array($tokenType, [...$expected, 9], true)) {
			throw new \Exception();
		}

		if ($tokenType === 1) {
			if ($openParenthesisCount > 0) {
				$comment .= $content;
			}
			$openParenthesisCount++;
			$expected = null;
			continue;
		}

		if ($tokenType === 2) {
			$openParenthesisCount--;
			if ($openParenthesisCount === 0) {
				$key = array_key_last($identifiers);
				if ($key !== null) {
					assertType('string|null', $comment);
					$identifiers[$key]['comment'] = $comment;
					$comment = null;
				}
				$expected = [4, 5];
			} else {
				$comment .= $content;
			}
			continue;
		}

		if ($openParenthesisCount > 0) {
			$comment .= $content;
			continue;
		}

		if ($tokenType === 3) {
			$identifiers[] = ['name' => $content, 'comment' => null];
			$expected = [4, 5, 1];
			continue;
		}

		if ($tokenType === 4) {
			$expected = [3];
			continue;
		}
	}

	return $identifiers;
}
