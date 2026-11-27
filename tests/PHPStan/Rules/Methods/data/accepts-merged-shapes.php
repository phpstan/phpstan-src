<?php declare(strict_types = 1);

namespace AcceptsMergedShapes;

/**
 * @phpstan-type PBool array{type: 'boolean', name: string}
 * @phpstan-type PKeyword array{type: 'keyword', name: string, fields?: array<string, array<string, string>>}
 * @phpstan-type P PBool|PKeyword|array{type: 'date', name: string, format: string}
 * @phpstan-type PObject array{type: 'object', properties: array<non-empty-string, P>}
 */
final class Foo
{

	/** @return P */
	public function merged(bool $b, string $name): array
	{
		return $b ? ['type' => 'boolean', 'name' => $name] : ['type' => 'keyword', 'name' => $name];
	}

	/** @return array{a: 1, b: 1}|array{a: 2, b: 2}|array{a: 1, b: 2}|array{a: 2, b: 1, c?: int} */
	public function mergedAtTwoKeys(int $i): array
	{
		return ['a' => $i === 1 ? 1 : 2, 'b' => $i === 3 ? 1 : 2];
	}

	/**
	 * @param PObject $mapping
	 * @return PObject
	 */
	public function nested(array $mapping): array
	{
		$mapping['properties'] += [
			'a' => ['type' => 'keyword', 'name' => 'a'],
			'b' => ['type' => 'boolean', 'name' => 'a'],
		];

		return $mapping;
	}

	/** @return P */
	public function notAccepted(bool $b, string $name): array
	{
		return $b ? ['type' => 'boolean', 'name' => $name] : ['type' => 'text', 'name' => $name];
	}

}
