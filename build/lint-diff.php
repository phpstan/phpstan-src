<?php declare(strict_types = 1);

/**
 * Advisory convention check for the lines a branch adds.
 *
 * Surfaces the CLAUDE.md conventions that recur on contributions, so they can be
 * resolved before pushing rather than after CI:
 *
 *   - instanceof of a Type class that has a method alternative on the Type interface
 *     (StringType -> isString(), EnumCaseObjectType -> getEnumCaseObject(), ...).
 *     Structural classes with no such method (NeverType, TemplateType, UnionType,
 *     MixedType) are intentionally not flagged; instanceof is idiomatic for those.
 *   - get_class(), which is brittle for type dispatch.
 *   - a new inline phpstan-ignore comment (fix the root cause; the baseline is
 *     for pre-existing errors only).
 *
 * Advisory by design: it prints candidates and exits 0. Pass --strict to exit 1 when
 * anything is flagged. Test-data fixtures (tests/**\/data/**) are skipped, and a
 * reviewed exception is acknowledged with `phpstan-lint-ok` on the line.
 *
 * Usage: php build/lint-diff.php [--strict] [<base-ref>]
 *        (default base: upstream/2.2.x, then origin/2.2.x, then 2.2.x)
 */

/**
 * @param list<string> $command
 * @return list<string>
 */
function lintDiffRun(array $command): array
{
	$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
	$process = proc_open($command, $descriptors, $pipes);
	if ($process === false) {
		return [];
	}

	$stdout = stream_get_contents($pipes[1]);
	fclose($pipes[1]);
	fclose($pipes[2]);
	proc_close($process);

	if (!is_string($stdout) || $stdout === '') {
		return [];
	}

	return explode("\n", rtrim($stdout, "\n"));
}

function lintDiffRefExists(string $ref): bool
{
	$descriptors = [1 => ['pipe', 'w'], 2 => ['pipe', 'w']];
	$process = proc_open(['git', 'rev-parse', '--verify', '--quiet', $ref], $descriptors, $pipes);
	if ($process === false) {
		return false;
	}

	// Drain the pipes before closing, otherwise git gets SIGPIPE writing the SHA
	// and exits non-zero, which would look like a missing ref.
	stream_get_contents($pipes[1]);
	stream_get_contents($pipes[2]);
	fclose($pipes[1]);
	fclose($pipes[2]);

	return proc_close($process) === 0;
}

$strict = false;
$base = null;
foreach (array_slice($argv ?? [], 1) as $arg) {
	if ($arg === '--strict') {
		$strict = true;
		continue;
	}

	$base = $arg;
}

if ($base === null) {
	foreach (['upstream/2.2.x', 'origin/2.2.x', '2.2.x'] as $candidate) {
		if (lintDiffRefExists($candidate)) {
			$base = $candidate;
			break;
		}
	}
}

if ($base === null) {
	fwrite(STDERR, "lint-diff: no base ref found; pass one as an argument\n");
	exit(2);
}

$diff = lintDiffRun(['git', 'diff', '--unified=0', $base, '--', '*.php']);

/** @var list<array{string, int, string}> $addedLines */
$addedLines = [];
$currentFile = null;
$lineNumber = 0;
foreach ($diff as $row) {
	if (substr($row, 0, 6) === '+++ b/') {
		$currentFile = substr($row, 6);
		continue;
	}

	if (substr($row, 0, 3) === '@@ ') {
		if (preg_match('#\+(\d+)#', $row, $matches) === 1) {
			$lineNumber = (int) $matches[1];
		}

		continue;
	}

	if (substr($row, 0, 3) === '+++') {
		continue;
	}

	if (substr($row, 0, 1) !== '+') {
		continue;
	}

	if ($currentFile !== null) {
		$addedLines[] = [$currentFile, $lineNumber, substr($row, 1)];
	}

	$lineNumber++;
}

/** @var list<array{label: string, pattern: string, guidance: string}> $checks */
$checks = [
	[
		'label' => 'instanceof a Type with a method alternative',
		'pattern' => '#\binstanceof\s+\\\\?((Constant)?(String|Integer|Float|Boolean)Type|ClassStringType|IntegerRangeType|NullType|EnumCaseObjectType)\b#',
		'guidance' => 'These classes have a Type method: use isString()/isInteger()/isFloat()/isBoolean()/isNull()/isClassString()/getEnumCaseObject(), or isSuperTypeOf(). Structural types (NeverType, TemplateType, UnionType, MixedType) are not flagged. Genuine exception? add phpstan-lint-ok on the line.',
	],
	[
		'label' => 'get_class() (brittle for type dispatch)',
		'pattern' => '#\bget_class\s*\(#',
		'guidance' => 'Prefer a Type method over get_class() dispatch. Not type dispatch (e.g. an error message)? add phpstan-lint-ok on the line.',
	],
	[
		'label' => 'new inline @phpstan-ignore',
		'pattern' => '#@phpstan-ignore#',
		'guidance' => 'Prefer fixing the root cause; the baseline is for pre-existing errors only. Justified and documented? add phpstan-lint-ok on the line.',
	],
];

$flagged = false;
foreach ($checks as $check) {
	$hits = [];
	foreach ($addedLines as [$file, $number, $content]) {
		if (strpos($content, 'phpstan-lint-ok') !== false) {
			continue;
		}

		if (preg_match('#^tests/.*/data/#', $file) === 1) {
			continue;
		}

		// The tool's own patterns and guidance carry the trigger strings as data.
		if ($file === 'build/lint-diff.php') {
			continue;
		}

		if (preg_match($check['pattern'], $content) !== 1) {
			continue;
		}

		$hits[] = sprintf('  %s:%d %s', $file, $number, trim($content));
	}

	if ($hits === []) {
		continue;
	}

	$flagged = true;
	printf("\n[%s]\n%s\n  => %s\n", $check['label'], implode("\n", $hits), $check['guidance']);
}

if (!$flagged) {
	printf("lint-diff: clean vs %s\n", $base);
	exit(0);
}

printf("\nlint-diff: candidates in added lines vs %s (review above; advisory).\n", $base);
exit($strict ? 1 : 0);
