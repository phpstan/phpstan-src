<?php declare(strict_types = 1);

namespace PHPStan\Type;

use function array_key_exists;
use function array_map;
use function array_slice;
use function count;
use function implode;
use function preg_match;
use function sprintf;
use function str_starts_with;
use function strlen;
use function substr;

/**
 * Keeps the reasons of accepts() and isSuperTypeOf() readable when the
 * compared types are big (bleeding edge).
 *
 * Reasons of nested arrays are offset paths
 * (`Offset 'foo' › 0 › 1 (array{int, int}): reason`) instead of repeating
 * the whole accepted type at every level. Only the innermost offset names
 * its types, and the accepted one only when it is not an array shape -
 * the reasons of an array shape already point into it. The same reason
 * found at many offsets is reported once, listing the offsets.
 */
final class AcceptsReasonHelper
{

	private const OFFSET_PREFIX = 'Offset ';

	private const OFFSET_PATH_SEPARATOR = ' › ';

	/**
	 * A path step as ConstantIntegerType and ConstantStringType describe
	 * themselves at VerbosityLevel::precise().
	 */
	private const OFFSET_PATTERN = '~\G(?:-?\d+|\'(?:[^\'\\\\]|\\\\.)*\'|"(?:[^"\\\\]|\\\\.)*")~';

	private const LISTED_ITEMS_LIMIT = 3;

	private const TIP_REASONS_LIMIT = 5;

	private const SUBJECT_DESCRIPTION_LENGTH_LIMIT = 120;

	public static function prependOffset(Type $offsetType, Type $offsetValueType, Type $acceptedValueType, VerbosityLevel $verbosity, string $reason): string
	{
		if (str_starts_with($reason, self::OFFSET_PREFIX)) {
			return sprintf(
				'Offset %s%s%s',
				$offsetType->describe(VerbosityLevel::precise()),
				self::OFFSET_PATH_SEPARATOR,
				substr($reason, strlen(self::OFFSET_PREFIX)),
			);
		}

		if ($acceptedValueType->isConstantArray()->yes()) {
			return sprintf(
				'Offset %s (%s): %s',
				$offsetType->describe(VerbosityLevel::precise()),
				$offsetValueType->describe($verbosity),
				$reason,
			);
		}

		return sprintf(
			'Offset %s (%s) does not accept type %s: %s',
			$offsetType->describe(VerbosityLevel::precise()),
			$offsetValueType->describe($verbosity),
			$acceptedValueType->describe($verbosity),
			$reason,
		);
	}

	/**
	 * The reasons as error tips: grouped by groupReasons(), and only the first
	 * few of many - the rest are typically the same problem in other members
	 * of a union.
	 *
	 * @param list<string> $reasons
	 * @return list<string>
	 */
	public static function forTip(array $reasons): array
	{
		$reasons = self::groupReasons($reasons);
		$count = count($reasons);
		if ($count <= self::TIP_REASONS_LIMIT) {
			return $reasons;
		}

		$reasons = array_slice($reasons, 0, self::TIP_REASONS_LIMIT);
		$reasons[] = sprintf('%d more reasons not shown.', $count - self::TIP_REASONS_LIMIT);

		return $reasons;
	}

	/**
	 * Merges reasons that differ only in their offset path into one
	 * (`Offsets 'a' › 0, 'b' › 0 and 5 more (int): reason`), in the order
	 * they first appear.
	 *
	 * @param list<string> $reasons
	 * @return list<string>
	 */
	public static function groupReasons(array $reasons): array
	{
		/** @var array<string, non-empty-list<string>> $pathsByRest */
		$pathsByRest = [];

		/** @var list<array{string}|array{null, string}> $order */
		$order = [];
		foreach ($reasons as $reason) {
			$split = self::splitOffsetPath($reason);
			if ($split === null) {
				$order[] = [$reason];
				continue;
			}

			[$path, $rest] = $split;
			if (array_key_exists($rest, $pathsByRest)) {
				$pathsByRest[$rest][] = $path;
				continue;
			}

			$pathsByRest[$rest] = [$path];
			$order[] = [null, $rest];
		}

		$grouped = [];
		foreach ($order as $item) {
			if (count($item) === 1) {
				$grouped[] = $item[0];
				continue;
			}

			$rest = $item[1];
			$paths = $pathsByRest[$rest];
			if (count($paths) === 1) {
				$grouped[] = self::OFFSET_PREFIX . $paths[0] . $rest;
				continue;
			}

			$grouped[] = 'Offsets ' . self::listItems($paths) . $rest;
		}

		return $grouped;
	}

	/**
	 * The accepted type as the subject of a reason, or $fallback when its
	 * description is too long to repeat - the error message describes it already.
	 */
	public static function describeSubject(Type $type, VerbosityLevel $level, string $fallback): string
	{
		$description = $type->describe($level);
		if (strlen($description) > self::SUBJECT_DESCRIPTION_LENGTH_LIMIT) {
			return $fallback;
		}

		return $description;
	}

	/**
	 * @param non-empty-list<string> $propertyNames
	 */
	public static function missingProperties(string $subject, array $propertyNames, bool $certainly): string
	{
		return sprintf(
			'%s %s have %s %s.',
			$subject,
			$certainly ? 'does not' : 'might not',
			count($propertyNames) === 1 ? 'property' : 'properties',
			self::listItems(array_map(static fn (string $propertyName): string => '$' . $propertyName, $propertyNames)),
		);
	}

	/**
	 * `a`, `a and b`, `a, b and c`, `a, b, c and 2 more`
	 *
	 * @param non-empty-list<string> $items
	 */
	private static function listItems(array $items): string
	{
		$count = count($items);
		if ($count === 1) {
			return $items[0];
		}

		if ($count > self::LISTED_ITEMS_LIMIT) {
			return sprintf(
				'%s and %d more',
				implode(', ', array_slice($items, 0, self::LISTED_ITEMS_LIMIT)),
				$count - self::LISTED_ITEMS_LIMIT,
			);
		}

		return sprintf('%s and %s', implode(', ', array_slice($items, 0, -1)), $items[$count - 1]);
	}

	/**
	 * @return array{string, string}|null the offset path and the rest of the reason
	 */
	private static function splitOffsetPath(string $reason): ?array
	{
		if (!str_starts_with($reason, self::OFFSET_PREFIX)) {
			return null;
		}

		$position = strlen(self::OFFSET_PREFIX);
		while (true) {
			if (preg_match(self::OFFSET_PATTERN, $reason, $matches, offset: $position) !== 1) {
				return null;
			}
			$position += strlen($matches[0]);
			if (substr($reason, $position, strlen(self::OFFSET_PATH_SEPARATOR)) !== self::OFFSET_PATH_SEPARATOR) {
				break;
			}
			$position += strlen(self::OFFSET_PATH_SEPARATOR);
		}

		$rest = substr($reason, $position);
		if ($rest === '' || ($rest[0] !== ' ' && $rest[0] !== ':')) {
			return null;
		}

		return [substr($reason, strlen(self::OFFSET_PREFIX), $position - strlen(self::OFFSET_PREFIX)), $rest];
	}

}
