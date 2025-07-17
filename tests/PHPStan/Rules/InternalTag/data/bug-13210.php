<?php // lint >= 8.0

/**
 * @internal
 *
 * @readonly
 */
final class DBInternal
{
	public static function phpValueToSql(int|string|BackedEnum|null $phpValue): string
	{
		return match (true) {
			$phpValue === null => 'NULL',
			is_string($phpValue) => "''",
			is_int($phpValue) => (string)$phpValue,
			$phpValue instanceof BackedEnum => self::phpValueToSql($phpValue->value),
		};
	}

}
