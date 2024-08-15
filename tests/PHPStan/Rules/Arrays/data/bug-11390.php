<?php declare(strict_types = 1);

namespace Bug11390;

/**
 * @param array<array{
 *   id: numeric-string,
 *   tagName: string
 * }> $tags
 * @param numeric-string $tagId
 */
function printTagName(array $tags, string $tagId): void
{
	// Adding the second `*` to either of the following lines makes the error disappear

	$tagsById = array_combine(array_column($tags, 'id'), $tags);
	if (false !== $tagsById) {
		echo $tagsById[$tagId]['tagName'] . PHP_EOL;
	}
}

printTagName(
	[
		['id' => '123', 'tagName' => 'abc'],
		['id' => '4.5', 'tagName' => 'def'],
		['id' => '6e78', 'tagName' => 'ghi']
	],
	'4.5'
);
