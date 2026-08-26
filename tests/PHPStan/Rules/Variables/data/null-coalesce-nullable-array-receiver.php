<?php declare(strict_types = 1);

namespace NullCoalesceNullableArrayReceiver;

/**
 * @phpstan-type data array{
 *     uuid: string,
 *     accepted_attempt: array{id_expiration_date: ?string}|null,
 *  }
 */
class KycResponse
{

	/**
	 * @param data $data
	 */
	public static function createFromData(array $data): ?string
	{
		return $data['accepted_attempt']['id_expiration_date'] ?? null;
	}

}
