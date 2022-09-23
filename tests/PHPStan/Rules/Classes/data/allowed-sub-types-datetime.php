<?php

namespace AllowedSubTypesDateTime;

use DateInterval;
use DateTime;
use DateTimeInterface;
use DateTimeZone;

class MyDateTimeImmutable extends DateTime {}

class MyDateTime implements DateTimeInterface
{

	public function diff(DateTimeInterface $targetObject, bool $absolute = false): DateInterval
	{
		return new DateInterval('P0D');
	}

	public function format(string $format): string
	{
		return '';
	}

	public function getOffset(): int
	{
		return 0;
	}

	public function getTimestamp(): int
	{
		return 0;
	}

	public function getTimezone(): DateTimeZone
	{
		return new DateTimeZone('UTC');
	}

	public function __wakeup(): void
	{
	}

	public function __serialize(): array
	{
		return [];
	}

	public function __unserialize(array $data): void
	{
	}

}
