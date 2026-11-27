<?php declare(strict_types = 1);

namespace PHPStan\Command\ErrorFormatter;

use Symfony\Component\Console\Formatter\OutputFormatter;
use function preg_match;
use function preg_replace_callback;

/**
 * Escapes the `<...>` in an error message or tip that are not console style tags,
 * such as `array<int, string>` in a type description, keeping `<fg=cyan>...</>`
 * and the like. Symfony's OutputFormatter prints a tag it cannot parse as it is,
 * but before finding out it measures the text up to it from the start of the
 * message, which takes time quadratic in the length of messages that describe
 * big types. An escaped `\<` is skipped before that measurement.
 *
 * @internal
 */
final class ConsoleTagEscaper
{

	private static ?OutputFormatter $formatter = null;

	public static function escape(string $text): string
	{
		$formatter = self::$formatter ??= new OutputFormatter();

		return (string) preg_replace_callback(
			'~(?<!\\\\)<(/?)([^<>]*)>~',
			static function (array $matches) use ($formatter): string {
				$tag = $matches[2];
				if (
					($matches[1] === '/' && $tag === '')
					|| $formatter->hasStyle($tag)
					|| preg_match('~^(?:(?:fg|bg|options|href)=[^;]+(?:;|$))+$~i', $tag) === 1
				) {
					return $matches[0];
				}

				return '\\<' . $matches[1] . $tag . '\\>';
			},
			$text,
		);
	}

}
