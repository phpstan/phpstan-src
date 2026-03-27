<?php // lint >= 8.1

namespace Bug11949;

function trans(string $key): string
{
	return $key;
}

trait EnumString
{

	/** @var array<string, string> */
	static protected ?array $_translatedValues;

	static public function getValueIndex(string $value): int
	{
		return ($i = array_search($value, self::NAMES)) === false ? -1 : $i;
	}

	/** @return array<string, string> */
	static public function getTranslatedValues(): array
	{
		return self::$_translatedValues ??= array_map(static::getTranslatedValue(...), array_combine(self::NAMES, self::NAMES));
	}

	static public function getTranslatedValue(string $value): string
	{
		return self::TRANSLATION ? trans(self::TRANSLATION . $value) : $value;
	}

}

abstract class UserStatus
{

	use EnumString;

	const ACTIVE = 'active';
	const PENDING = 'pending';
	const BLOCKED = 'blocked';

	protected const NAMES = [
		self::ACTIVE,
		self::PENDING,
		self::BLOCKED,
	];

	protected const TRANSLATION = 'users.statuses.';

}

abstract class SystemCheckStatus
{

	use EnumString;

	const SUCCESS = 'success';
	const FAILURE = 'failure';

	protected const NAMES = [
		self::SUCCESS,
		self::FAILURE,
	];

	protected const TRANSLATION = '';

}
