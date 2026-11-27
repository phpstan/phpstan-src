<?php declare(strict_types = 1);

namespace Bug15854;

use function PHPStan\Testing\assertType;

interface Flags
{
	/** @return list<int> */
	public function getValues(): array;

}

interface Data
{
	public function getFlags(): Flags;
	public function getOther(): ?string;
}

interface Entry
{
	public function getId(): int;
	public function getData(): ?Data;
}

/**
 * @param list<Entry> $entries
 */
function build(array $entries): void
{
	/** @var array{all: array{known: list<int>, other: ?string, otherEnable: bool, ids: list<int>}|array{}, items: array<string, array{id: int, known: list<int>, other: ?string, otherEnable: bool}>, perItem?: bool} $values */
	$values = [
		'all' => [],
		'items' => [],
	];

	foreach ($entries as $entry) {
		$data = $entry->getData();
		if ($data === null) {
			continue;
		}


		$key = sprintf('item%d', $entry->getId());

		$values['items'][$key] = [
			'id' => $entry->getId(),
			'known' => $data->getFlags()->getValues(),
			'other' => $data->getOther(),
			'otherEnable' => $data->getOther() !== null,
		];

		if ($values['all'] === []) {
			$values['all'] = $values['items'][$key];
			unset($values['all']['id']);
			$values['all']['ids'] = [$entry->getId()];

			continue;
		}

		assertType('array{known: list<int>|null, other: string|null, otherEnable: bool, ids: list<int>}', $values['all']);
		if (
			($values['all']['known'] ?? []) === $data->getFlags()->getValues()
			&& $values['all']['other'] === $data->getOther()
		) {
			$values['all']['ids'][] = $entry->getId();

			continue;
		}

		$values['perItem'] = true;
		$values['all'] = [
			'known' => null,
			'other' => null,
			'otherEnable' => false,
			'ids' => [],
		];
	}

	echo count($values['items']), isset($values['perItem']) ? 'y' : 'n';
}

