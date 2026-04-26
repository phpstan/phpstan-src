<?php declare(strict_types=1);

namespace ArrayKeyLastExisting;

use function PHPStan\Testing\assertType;

/**
 * Mirrors the RichParser pattern: the array starts empty, gets entries
 * appended in some loop branches, and an existing entry's nested key is
 * updated in others. `if ($key !== null)` should be enough to let PHPStan
 * track that `$arr[$key]` exists and the deep write should preserve the
 * outer shape, just like `isset($arr[$key])` does.
 */
function appendThenUpdateLast(string $name, string $comment): void
{
	$identifiers = [];
	$c = rand(100, 200);
	for ($i = 0; $i < $c; $i++) {
		if (rand(0, 1) === 1) {
			$key = array_key_last($identifiers);
			if ($key !== null) {
				$identifiers[$key]['comment'] = $comment;
			}
			continue;
		}

		$identifiers[] = ['name' => $name, 'comment' => null];
	}

	assertType('list<array{name: string, comment: string|null}>', $identifiers);
}

function appendThenUpdateFirst(string $name, string $comment): void
{
	$identifiers = [];
	$c = rand(100, 200);
	for ($i = 0; $i < $c; $i++) {
		if (rand(0, 1) === 1) {
			$key = array_key_first($identifiers);
			if ($key !== null) {
				$identifiers[$key]['comment'] = $comment;
			}
			continue;
		}

		$identifiers[] = ['name' => $name, 'comment' => null];
	}

	assertType('list<array{name: string, comment: string|null}>', $identifiers);
}

/**
 * @param list<array{name: 'x', comment: null}> $list
 */
function maybeEmptyArray(array $list): void
{
	$key = array_key_last($list);
	if ($key !== null) {
		assertType('array{name: \'x\', comment: null}', $list[$key]);
		$list[$key]['comment'] = 'hello';
		assertType('non-empty-list<array{name: \'x\', comment: \'hello\'}>', $list);
	}
}
