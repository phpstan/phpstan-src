<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug14429;

function throw_if(bool $condition, string $message): void
{
	if ($condition) { throw new \Exception($message); }
}

class Foo
{
	/**
	 * @param list<string> $tags
	 * @param list<float> $scores
	 * @param \ArrayObject<string, string> $stringMap
	 * @param \ArrayObject<string, int> $intKeyMap
	 */
	public function __construct(
		public array $tags,
		public array $scores,
		public ?\ArrayObject $stringMap = null,
		public ?\ArrayObject $intKeyMap = null,
	) {
		foreach ($tags as $tagsItem) {
			throw_if(!is_string($tagsItem), 'tags item must be string');
		}
		foreach ($scores as $scoresItem) {
			throw_if(!is_int($scoresItem) && !is_float($scoresItem), 'scores item must be number');
		}
		if ($stringMap !== null) {
			foreach ($stringMap as $stringMapValue) {
				throw_if(!is_string($stringMapValue), 'stringMap value must be string');
			}
		}
		if ($intKeyMap !== null) {
			foreach ($intKeyMap as $intKeyMapValue) {
				throw_if(!is_int($intKeyMapValue), 'intKeyMap value must be int');
			}
		}
	}
}
