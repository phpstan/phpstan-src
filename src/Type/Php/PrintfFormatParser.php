<?php declare(strict_types = 1);

namespace PHPStan\Type\Php;

use PHPStan\DependencyInjection\AutowiredService;
use function in_array;
use function ltrim;
use function strlen;
use function strpos;
use function substr;

/**
 * Follows php_formatted_print() in php-src (PHP 8.0+) to tell which arguments
 * a printf-family format consumes, and whether the format itself is invalid.
 */
#[AutowiredService]
final class PrintfFormatParser
{

	private const INT_MAX = 2147483647;

	private const SPECIFIERS = 'sdueEfFgGhHcoxXb%';

	private const FLAGS = [' ', '0', '-', '+'];

	private const ARG_NUM_NEXT = -1;

	/**
	 * Returns the argument uses in the order the format consumes them, or null
	 * when the format throws ValueError for any arguments. Index 0 is the first
	 * argument after the format; `width` and `precision` are `*` and `.*` uses.
	 *
	 * @return list<array{index: int, kind: 'value'|'width'|'precision', specifier: string}>|null
	 */
	public function parse(string $format): ?array
	{
		$length = strlen($format);
		$i = 0;
		$nextArgument = 0;
		$uses = [];

		while ($i < $length) {
			$percent = strpos($format, '%', $i);
			if ($percent === false) {
				break;
			}

			$i = $percent + 1;
			if ($i < $length && $format[$i] === '%') {
				$i++;
				continue;
			}

			$pending = [];
			if ($i < $length && self::isAsciiLetter($format[$i])) {
				$argument = self::ARG_NUM_NEXT;
			} else {
				$argument = self::parseArgumentNumber($format, $length, $i);
				if ($argument === null) {
					return null;
				}

				while ($i < $length) {
					$char = $format[$i];
					if (in_array($char, self::FLAGS, true)) {
						$i++;
						continue;
					}

					if ($char === "'") {
						if ($length - $i <= 1) {
							return null;
						}

						$i += 2;
						continue;
					}

					break;
				}

				if ($i < $length && $format[$i] === '*') {
					$i++;
					$widthArgument = self::parseArgumentNumber($format, $length, $i);
					if ($widthArgument === null) {
						return null;
					}
					if ($widthArgument === self::ARG_NUM_NEXT) {
						$widthArgument = $nextArgument++;
					}
					$pending[] = [$widthArgument, 'width'];
				} elseif ($i < $length && self::isDigit($format[$i])) {
					if (self::parseNumber($format, $length, $i) >= self::INT_MAX) {
						return null;
					}
				}

				if ($i < $length && $format[$i] === '.') {
					$i++;
					if ($i < $length && $format[$i] === '*') {
						$i++;
						$precisionArgument = self::parseArgumentNumber($format, $length, $i);
						if ($precisionArgument === null) {
							return null;
						}
						if ($precisionArgument === self::ARG_NUM_NEXT) {
							$precisionArgument = $nextArgument++;
						}
						$pending[] = [$precisionArgument, 'precision'];
					} elseif ($i < $length && self::isDigit($format[$i])) {
						if (self::parseNumber($format, $length, $i) >= self::INT_MAX) {
							return null;
						}
					}
				}
			}

			if ($i < $length && $format[$i] === 'l') {
				$i++;
			}

			if ($argument === self::ARG_NUM_NEXT) {
				$argument = $nextArgument++;
			}

			if ($i >= $length) {
				return null;
			}

			$specifier = $format[$i];
			if (strpos(self::SPECIFIERS, $specifier) === false) {
				return null;
			}

			foreach ($pending as [$index, $kind]) {
				$uses[] = ['index' => $index, 'kind' => $kind, 'specifier' => $specifier];
			}
			$uses[] = ['index' => $argument, 'kind' => 'value', 'specifier' => $specifier];
			$i++;
		}

		return $uses;
	}

	/**
	 * @param list<array{index: int, kind: 'value'|'width'|'precision', specifier: string}> $uses
	 */
	public function getRequiredArgumentsCount(array $uses): int
	{
		$count = 0;
		foreach ($uses as $use) {
			if ($use['index'] < $count) {
				continue;
			}

			$count = $use['index'] + 1;
		}

		return $count;
	}

	/**
	 * Returns the zero-based index of a `n$` argument number, ARG_NUM_NEXT when
	 * there is none, or null when it is out of range.
	 */
	private static function parseArgumentNumber(string $format, int $length, int &$i): ?int
	{
		$end = $i;
		while ($end < $length && self::isDigit($format[$end])) {
			$end++;
		}

		if ($end >= $length || $format[$end] !== '$') {
			return self::ARG_NUM_NEXT;
		}

		$number = self::parseNumber($format, $length, $i);
		$i++;
		if ($number <= 0 || $number >= self::INT_MAX) {
			return null;
		}

		return $number - 1;
	}

	private static function parseNumber(string $format, int $length, int &$i): int
	{
		$start = $i;
		while ($i < $length && self::isDigit($format[$i])) {
			$i++;
		}

		$digits = ltrim(substr($format, $start, $i - $start), '0');
		if ($digits === '') {
			return 0;
		}

		if (strlen($digits) > 10) {
			return self::INT_MAX;
		}

		return (int) $digits;
	}

	private static function isDigit(string $char): bool
	{
		return $char >= '0' && $char <= '9';
	}

	private static function isAsciiLetter(string $char): bool
	{
		return ($char >= 'a' && $char <= 'z') || ($char >= 'A' && $char <= 'Z');
	}

}
