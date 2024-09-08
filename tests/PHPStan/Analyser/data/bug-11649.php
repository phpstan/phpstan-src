<?php

namespace Bug11649;

use DateTime;

class Date
{
	/**
	 * Characters used in formats accepted by date()
	 */
	protected const DATE_FORMAT_CHARACTERS = 'AaBcDdeFgGHhIijlLMmnNoOpPrsSTtUuvWwyYzZ';

	/**
	 * Regex used to parse formats accepted by date()
	 */
	protected const DATE_FORMAT_REGEX = '/(?P<escaped>(?:\\\[A-Za-z])+)|[' . self::DATE_FORMAT_CHARACTERS . ']|(?P<invalid>[A-Za-z])/';

	/**
	 * Formats a DateTime object using the current translation for weekdays and months
	 * @param mixed $translation
	 */
	public static function formatDateTime(DateTime $dateTime, string $format, ?string $language , $translation): ?string
	{
		return preg_replace_callback(
			self::DATE_FORMAT_REGEX,
			fn(array $matches): string => match ($matches[0]) {
				'M'     => $translation->getStrings('date.months.short')[$dateTime->format('n') - 1],
				'F'     => $translation->getStrings('date.months.long')[$dateTime->format('n') - 1],
				'D'     => $translation->getStrings('date.weekdays.short')[(int) $dateTime->format('w')],
				'l'     => $translation->getStrings('date.weekdays.long')[(int) $dateTime->format('w')],
				'r'     => static::formatDateTime($dateTime, DateTime::RFC2822, null, $translation),
				default => $dateTime->format($matches[1] ?? $matches[0])
			},
			$format
		);
	}
}
