<?php declare(strict_types = 1);

namespace Foo;

use function trim;

class Foo
{

	public const FIELD_TITLE = 'title';
	public const FIELD_SOURCE = 'source';
	public const FIELD_BODY = 'body';
	public const EMPTY_NOTE_BODY = '-';

	public const FIELD_NOTES = 'notes';
	public const SUBFIELD_NOTE = 'note';

	/**
	 * @param array<mixed> $data
	 * @return array<mixed>
	 */
	private function someMethod(array $data): array
	{
		foreach ($data[self::FIELD_NOTES][self::SUBFIELD_NOTE] ?? [] as $index => $noteData) {
			$noteTitle = $noteData[self::FIELD_TITLE] ?? null;
			$noteSource = $noteData[self::FIELD_SOURCE] ?? null;
			$noteBody = $noteData[self::FIELD_BODY] ?? null;

			if ($noteBody === null || trim($noteBody) === '') {
				$data[self::FIELD_NOTES] = self::EMPTY_NOTE_BODY;
			}
		}

		if (isset($data[self::FIELD_NOTES][self::SUBFIELD_NOTE])) {}

		return $data;
	}

}
