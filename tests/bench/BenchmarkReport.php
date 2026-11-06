<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use function abs;
use function array_key_first;
use function array_keys;
use function array_map;
use function array_shift;
use function array_sum;
use function array_unique;
use function count;
use function implode;
use function max;
use function mb_str_pad;
use function mb_strlen;
use function number_format;
use function round;
use function rtrim;
use function sprintf;
use function str_replace;
use const STR_PAD_LEFT;
use const STR_PAD_RIGHT;

/**
 * Turns the results of BenchmarkComparator into what compare.php prints to the job log, writes
 * to the job summary and reports as annotations on the benchmark files.
 *
 * Each benchmark goes into one section by its worst metric, so a benchmark slower in instructions
 * but lower in memory is listed as slower, with both metrics.
 *
 * @phpstan-type Result array{benchmark: string, metric: value-of<BenchmarkComparator::METRICS>, expected: int|null, actual: int|null, change: float|null, allowedChange: float|null, status: BenchmarkComparator::STATUS_*}
 */
final class BenchmarkReport
{

	/** The sections in the order they are shown, the first one a benchmark has a metric in wins */
	private const SECTIONS = [
		BenchmarkComparator::STATUS_SLOWER => [
			'title' => 'Slower than expected',
			'alert' => 'CAUTION',
			'color' => '31',
			'annotation' => 'error',
			'annotationTitle' => '%s is slower than expected',
			'text' => 'This change makes PHPStan do more work on these files. Find out why before merging. If it is the price of a fix, accept the new numbers.',
		],
		BenchmarkComparator::STATUS_NOT_MEASURED => [
			'title' => 'Not measured',
			'alert' => 'WARNING',
			'color' => '33',
			'annotation' => 'error',
			'annotationTitle' => '%s was not measured',
			'text' => 'These benchmarks failed or timed out, see the log of their shard. A benchmark removed from data/ also has to be removed from expected.neon.',
		],
		BenchmarkComparator::STATUS_NOT_EXPECTED => [
			'title' => 'New benchmarks',
			'alert' => 'NOTE',
			'color' => '34',
			'annotation' => 'warning',
			'annotationTitle' => '%s has no expected numbers',
			'text' => 'expected.neon has no numbers for these yet. This does not fail the build, but accept the new numbers.',
		],
		BenchmarkComparator::STATUS_FASTER => [
			'title' => 'Faster than expected',
			'alert' => 'TIP',
			'color' => '32',
			'annotation' => 'warning',
			'annotationTitle' => '%s is faster than expected',
			'text' => 'Nice! This does not fail the build, but accept the new numbers, so a later slowdown back to the old ones does not go unnoticed.',
		],
	];

	private const HEADER = ['Benchmark', 'Metric', 'Expected', 'Measured', 'Change', 'Allowed'];

	/**
	 * @param list<Result> $results
	 * @param string $acceptCommand shell commands that replace expected.neon with the measured numbers
	 */
	public static function renderTerminal(array $results, string $acceptCommand, bool $colors): string
	{
		$style = static fn (string $code, string $text): string => $colors ? "\e[" . $code . 'm' . $text . "\e[0m" : $text;
		$sections = self::groupBySection($results);

		$output = $style('1', self::headline($results, $sections)) . "\n";
		$output .= self::toleranceLine() . "\n";
		foreach ($sections as $status => $rows) {
			$section = self::SECTIONS[$status];
			$output .= "\n" . $style('1;' . $section['color'], $section['title']) . "\n";
			$output .= $section['text'] . "\n\n";
			$output .= self::renderTextTable(self::tableRows($rows), '  ');
		}
		if ($sections === []) {
			return $output;
		}

		$output .= "\n" . $style('1', 'Reproduce locally') . ', on this branch and on the base branch:' . "\n";
		$output .= '  ' . self::reproduceCommand($sections) . "\n";
		$output .= "\n" . $style('1', 'Accept the new numbers') . "\n";
		$output .= '  ' . str_replace("\n", "\n  ", $acceptCommand) . "\n";

		return $output;
	}

	/**
	 * Every benchmark with its metrics, for the collapsed part of the log.
	 *
	 * @param list<Result> $results
	 */
	public static function renderTerminalTable(array $results): string
	{
		return self::renderTextTable([
			[...self::HEADER, 'Status'],
			...array_map(static fn (array $result): array => [...self::tableRow($result), $result['status']], $results),
		], '');
	}

	/**
	 * @param list<Result> $results
	 */
	public static function renderMarkdown(array $results, string $acceptCommand): string
	{
		$sections = self::groupBySection($results);

		$output = '## ' . self::headline($results, $sections) . "\n\n";
		$output .= self::toleranceLine() . "\n";
		foreach ($sections as $status => $rows) {
			$section = self::SECTIONS[$status];
			$output .= sprintf("\n> [!%s]\n> **%s**\n> %s\n\n", $section['alert'], $section['title'], $section['text']);
			$output .= self::renderMarkdownTable(self::tableRows($rows));
		}
		if ($sections !== []) {
			$output .= "\n### Reproduce locally\n\nOn this branch and on the base branch:\n\n```\n" . self::reproduceCommand($sections) . "\n```\n";
			$output .= "\n### Accept the new numbers\n\n```\n" . $acceptCommand . "\n```\n";
		}

		$output .= "\n<details><summary>All benchmarks</summary>\n\n";
		$output .= self::renderMarkdownTable([
			[...self::HEADER, 'Status'],
			...array_map(static fn (array $result): array => [...self::tableRow($result), $result['status']], $results),
		]);

		return $output . "\n</details>\n";
	}

	/**
	 * GitHub Actions workflow commands that annotate the benchmark files, one per benchmark.
	 *
	 * @param list<Result> $results
	 */
	public static function renderAnnotations(array $results): string
	{
		$output = '';
		foreach (self::groupBySection($results) as $status => $rows) {
			$byBenchmark = [];
			foreach ($rows as $row) {
				$byBenchmark[$row['benchmark']][] = $row;
			}
			foreach ($byBenchmark as $benchmark => $benchmarkRows) {
				$changes = [];
				foreach ($benchmarkRows as $row) {
					if ($row['change'] === null) {
						continue;
					}
					$changes[] = sprintf(
						'%s %s (%s, expected %s, allowed ±%s %%)',
						$row['metric'],
						self::formatChange($row['change']),
						self::formatShort($row['metric'], $row['actual']),
						self::formatShort($row['metric'], $row['expected']),
						self::formatPercent($row['allowedChange'] ?? 0.0),
					);
				}
				$output .= sprintf(
					"::%s file=tests/bench/data/%s,line=1,title=%s::%s\n",
					self::SECTIONS[$status]['annotation'],
					$benchmark,
					self::escapeAnnotation(sprintf(self::SECTIONS[$status]['annotationTitle'], $benchmark)),
					self::escapeAnnotation($changes === [] ? self::SECTIONS[$status]['text'] : implode("\n", $changes)),
				);
			}
		}

		return $output;
	}

	/**
	 * @param list<Result> $results
	 * @return array<key-of<self::SECTIONS>, non-empty-list<Result>> only the sections with a benchmark, in the order of SECTIONS
	 */
	private static function groupBySection(array $results): array
	{
		$statusesByBenchmark = [];
		foreach ($results as $result) {
			$statusesByBenchmark[$result['benchmark']][$result['status']] = true;
		}

		$sections = [];
		foreach ($results as $result) {
			if ($result['status'] === BenchmarkComparator::STATUS_OK) {
				continue;
			}
			foreach (array_keys(self::SECTIONS) as $status) {
				if (isset($statusesByBenchmark[$result['benchmark']][$status])) {
					$sections[$status][] = $result;
					break;
				}
			}
		}

		$ordered = [];
		foreach (array_keys(self::SECTIONS) as $status) {
			if (!isset($sections[$status])) {
				continue;
			}

			$ordered[$status] = $sections[$status];
		}

		return $ordered;
	}

	/**
	 * @param list<Result> $results
	 * @param array<key-of<self::SECTIONS>, non-empty-list<Result>> $sections
	 */
	private static function headline(array $results, array $sections): string
	{
		$benchmarkCounts = [];
		foreach ($sections as $status => $rows) {
			$benchmarkCounts[$status] = count(array_unique(array_map(static fn (array $row): string => $row['benchmark'], $rows)));
		}
		$allBenchmarks = count(array_unique(array_map(static fn (array $result): string => $result['benchmark'], $results)));
		$unchanged = $allBenchmarks - array_sum($benchmarkCounts);
		if ($benchmarkCounts === []) {
			return sprintf('Benchmarks passed: all %d match tests/bench/expected.neon', $allBenchmarks);
		}

		$parts = [];
		foreach ($benchmarkCounts as $status => $benchmarkCount) {
			$parts[] = sprintf('%d %s', $benchmarkCount, $status === BenchmarkComparator::STATUS_NOT_EXPECTED ? 'new' : $status);
		}
		$parts[] = sprintf('%d unchanged', $unchanged);

		return (BenchmarkComparator::fails($results) ? 'Benchmarks failed: ' : 'Benchmarks passed: ') . implode(', ', $parts);
	}

	private static function toleranceLine(): string
	{
		$starts = array_keys(BenchmarkComparator::DURATION_TOLERANCES);
		$ranges = [];
		foreach ($starts as $i => $start) {
			$tolerance = BenchmarkComparator::DURATION_TOLERANCES[$start];
			$ranges[] = $i === 0
				? sprintf('±%d ms below %d ms', (int) round($tolerance * $starts[1]), $starts[1])
				: sprintf('±%d %% from %d ms', (int) round($tolerance * 100), $start);
		}

		return sprintf(
			'Allowed difference from tests/bench/expected.neon: instructions and estimatedCycles by the time they take at %s instructions or %s cycles per ms: %s, each at least what the range below allows; ±%d %% peakMemory.',
			number_format(BenchmarkComparator::PER_MILLISECOND['instructions'] / 1e6, 1) . ' M',
			number_format(BenchmarkComparator::PER_MILLISECOND['estimatedCycles'] / 1e6, 1) . ' M',
			implode(', ', $ranges),
			(int) round(BenchmarkComparator::PEAK_MEMORY_TOLERANCE * 100),
		);
	}

	/**
	 * @param array<key-of<self::SECTIONS>, non-empty-list<Result>> $sections
	 */
	private static function reproduceCommand(array $sections): string
	{
		$worst = null;
		foreach ($sections[array_key_first($sections) ?? BenchmarkComparator::STATUS_SLOWER] ?? [] as $row) {
			if ($worst !== null && abs($row['change'] ?? 0.0) <= abs($worst['change'] ?? 0.0)) {
				continue;
			}

			$worst = $row;
		}
		if ($worst === null) {
			return '';
		}

		return 'bin/phpstan analyse -c tests/bench/phpstan.neon --debug tests/bench/data/' . $worst['benchmark'];
	}

	/**
	 * @param list<Result> $rows
	 * @return non-empty-list<list<string>>
	 */
	private static function tableRows(array $rows): array
	{
		return [self::HEADER, ...array_map(static fn (array $row): array => self::tableRow($row), $rows)];
	}

	/**
	 * @param Result $result
	 * @return list<string>
	 */
	private static function tableRow(array $result): array
	{
		return [
			$result['benchmark'],
			$result['metric'],
			self::formatValue($result['metric'], $result['expected']),
			self::formatValue($result['metric'], $result['actual']),
			$result['change'] === null ? '-' : self::formatChange($result['change']),
			$result['allowedChange'] === null ? '-' : '±' . self::formatPercent($result['allowedChange']) . ' %',
		];
	}

	/**
	 * Left-aligns the first two columns and right-aligns the numbers.
	 *
	 * @param list<list<string>> $rows the first one is the header
	 */
	private static function renderTextTable(array $rows, string $indent): string
	{
		$widths = [];
		foreach ($rows as $row) {
			foreach ($row as $i => $cell) {
				$widths[$i] = max($widths[$i] ?? 0, mb_strlen($cell));
			}
		}

		$lines = [];
		foreach ($rows as $row) {
			$cells = [];
			foreach ($row as $i => $cell) {
				$cells[] = mb_str_pad($cell, $widths[$i], ' ', $i < 2 || $i > 5 ? STR_PAD_RIGHT : STR_PAD_LEFT);
			}
			$lines[] = rtrim($indent . implode('   ', $cells));
		}

		return implode("\n", $lines) . "\n";
	}

	/**
	 * @param non-empty-list<list<string>> $rows the first one is the header
	 */
	private static function renderMarkdownTable(array $rows): string
	{
		$header = array_shift($rows);
		$output = '| ' . implode(' | ', $header) . " |\n";
		$output .= '|' . implode('|', array_map(static fn (int $i): string => $i < 2 || $i > 5 ? '---' : '--:', array_keys($header))) . "|\n";
		foreach ($rows as $row) {
			$row[0] = '`' . $row[0] . '`';
			$output .= '| ' . implode(' | ', $row) . " |\n";
		}

		return $output;
	}

	private static function formatValue(string $metric, ?int $value): string
	{
		if ($value === null) {
			return '-';
		}
		if ($metric === 'peakMemory') {
			return number_format($value / 1024 / 1024, 1) . ' MB';
		}

		return number_format($value);
	}

	private static function formatShort(string $metric, ?int $value): string
	{
		if ($value === null || $metric === 'peakMemory') {
			return self::formatValue($metric, $value);
		}

		if ($value < 1_000_000_000) {
			return number_format($value / 1e6) . ' M';
		}

		return number_format($value / 1e9, 2) . ' G';
	}

	private static function formatPercent(float $fraction): string
	{
		$percent = $fraction * 100;

		return $percent >= 10 || $percent === round($percent) ? (string) (int) round($percent) : sprintf('%.1f', $percent);
	}

	private static function formatChange(float $change): string
	{
		return sprintf('%+.1f %%', $change * 100);
	}

	/** https://github.com/actions/toolkit/blob/main/packages/core/src/command.ts */
	private static function escapeAnnotation(string $text): string
	{
		return str_replace(['%', "\r", "\n"], ['%25', '%0D', '%0A'], $text);
	}

}
