<?php declare(strict_types = 1);

namespace Bug7686;

class Foo
{
	/**
	 * @param array<array{name: string, type: string}> $input
	 * @return array<'return'|int, string>
	 */
	public static function test(array $input): array
	{
		$output = [];
		foreach($input as $match) {
			if (array_key_exists($match['name'], $output) == false) {
				$output[$match['name']] = '';
			}
			if (($match['type'] === '') || (in_array($match['type'], explode('|', $output[$match['name']]), true) === true)) {
				continue;
			}
			$output[$match['name']] = ($output[$match['name']] === '' ? $match['type'] : $output[$match['name']] . '|' . $match['type']);
		}
		return $output;
	}
}
