<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug13655;

use function PHPStan\Testing\assertType;

class HelloWorld
{
	public function generateStructure(): void
	{
		$structure = [];
		$data = [
			['a' => new Id('abc'), 'b' => new Id('cde')],
			['a' => new Id('kyp'), 'b' => new Id('efg')],
			['a' => new Id('hij'), 'b' => new Id('efg')]
		];

		
		foreach($data as $pairing) {
			self::addEntryForId($pairing['a'], $structure);
			self::addEntryForId($pairing['b'], $structure);

			$votedFor = $pairing[rand(0,1) === 0 ? 'a' : 'b'];
			if ($pairing['a']->asString() === $votedFor->asString()) {
                $structure[$votedFor->asString()]['wins'][] = $pairing['b'];
                $structure[$pairing['b']->asString()]['losses'][] = $pairing['a'];
            } else {
                $structure[$votedFor->asString()]['wins'][] = $pairing['a'];
                $structure[$pairing['a']->asString()]['losses'][] = $pairing['b'];
            }
		}
		// a write to a key addEntryForId() may not have added creates an entry without the other key
		assertType('non-empty-array<string, array{wins: list{0: Bug13655\\Id, 1?: Bug13655\\Id, 2?: Bug13655\\Id}, losses?: array{0: Bug13655\\Id, 1?: Bug13655\\Id}}|array{wins?: list{0: Bug13655\\Id, 1?: Bug13655\\Id, 2?: Bug13655\\Id}, losses: list{0: Bug13655\\Id, 1?: Bug13655\\Id, 2?: Bug13655\\Id}}>', $structure);
	}

	/**
     * @param Id $id
     * @param array<string,array{'wins': Id[], 'losses': Id[]}> $data
     * @return array<string,array{'wins': Id[], 'losses': Id[]}>
     */
    private static function addEntryForId(Id $id, array $data): array
    {
        if (!array_key_exists($id->asString(), $data)) {
            $data[$id->asString()] = ['wins' => [], 'losses' => []];
        }

        return $data;
    }
	
}

class Id {
	public function __construct(private string $id){}
	public function asString(): string {
		return $this->id;
	}
}


class HelloWorld2
{
	public function generateStructure(): void
	{
		$structure = [];
		$data = [
			['a' => new Id('abc'), 'b' => new Id('cde')],
			['a' => new Id('kyp'), 'b' => new Id('efg')],
			['a' => new Id('hij'), 'b' => new Id('efg')]
		];

		
		foreach($data as $pairing) {
			$structure = self::addEntryForId($pairing['a'], $structure);
			$structure = self::addEntryForId($pairing['b'], $structure);

			$votedFor = $pairing[rand(0,1) === 0 ? 'a' : 'b'];
			if ($pairing['a']->asString() === $votedFor->asString()) {
                $structure[$votedFor->asString()]['wins'][] = $pairing['b'];
                $structure[$pairing['b']->asString()]['losses'][] = $pairing['a'];
            } else {
                $structure[$votedFor->asString()]['wins'][] = $pairing['a'];
                $structure[$pairing['a']->asString()]['losses'][] = $pairing['b'];
            }
		}
		assertType('non-empty-array<string, array{wins: array<Bug13655\\Id>, losses: array<Bug13655\\Id>}|array{wins: non-empty-array<Bug13655\\Id>, losses?: array<Bug13655\\Id>}|array{wins?: array<Bug13655\\Id>, losses: non-empty-array<Bug13655\\Id>}>', $structure);
	}

	/**
     * @param Id $id
     * @param array<string,array{'wins': Id[], 'losses': Id[]}> $data
     * @return array<string,array{'wins': Id[], 'losses': Id[]}>
     */
    private static function addEntryForId(Id $id, array $data): array
    {
        if (!array_key_exists($id->asString(), $data)) {
            $data[$id->asString()] = ['wins' => [], 'losses' => []];
        }

        return $data;
    }
	
}
