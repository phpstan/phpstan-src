<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15355;

/**
 * Throws if $pattern is not a valid regular expression.
 */
function assertValidPattern(string $pattern): void
{
	set_error_handler(static function (int $type, string $message): never {
		throw new \InvalidArgumentException($message);
	});
	try {
		preg_match($pattern, '');
		preg_grep($pattern, []);
		preg_split($pattern, '');
		preg_replace_callback($pattern, static fn (array $m): string => $m[0], '');
	} finally {
		restore_error_handler();
	}
}

/**
 * @phpstan-pure
 * @return list<string>|false
 */
function splitPure(string $pattern, string $subject): array|false
{
	return preg_split($pattern, $subject);
}

/**
 * @phpstan-pure
 * @param array<string> $input
 * @return array<mixed>|false
 */
function grepPure(string $pattern, array $input): array|false
{
	return preg_grep($pattern, $input);
}

/**
 * @phpstan-pure
 */
function matchPure(string $pattern, string $subject): bool
{
	return preg_match($pattern, $subject) === 1;
}
