<?php declare(strict_types = 1);

namespace PHPStan\Analyser\Generics;

use function array_sum;
use function file_put_contents;
use function fwrite;
use function getenv;
use function in_array;
use function register_shutdown_function;
use function sprintf;
use const FILE_APPEND;
use const LOCK_EX;
use const PHP_EOL;
use const STDERR;

/**
 * Counters describing what the two-pass body walk did, for benchmarks only.
 *
 * Enabled by the PHPSTAN_TEMPLATE_CLAMP_STATS environment variable: `1`
 * prints the counters to STDERR at shutdown, any other value is a file path
 * they are appended to (worker processes of a parallel run have no visible
 * STDERR). Forked workers exit without running shutdown functions, so collect
 * with --debug.
 */
final class TemplateArgumentStats
{

	public static bool $enabled = false;

	private static ?string $outputFile = null;

	private static bool $shutdownRegistered = false;

	/** @var array<string, int> */
	private static array $counters = [
		'bodiesWalked' => 0,
		'bodiesWithSites' => 0,
		'sitesCreated' => 0,
		'statementsTotal' => 0,
		'statementsReplayed' => 0,
		'statementsReWalked' => 0,
		'earlyExits' => 0,
		'resolvedBySend' => 0,
		'resolvedWithLowerBounds' => 0,
		'resolvedToInitial' => 0,
		'resolvedUnconstrained' => 0,
	];

	public static function enableFromEnvironment(): void
	{
		$value = getenv('PHPSTAN_TEMPLATE_CLAMP_STATS');
		if (in_array($value, [false, ''], true)) {
			return;
		}

		self::$enabled = true;
		self::$outputFile = $value === '1' ? null : $value;
		if (self::$shutdownRegistered) {
			return;
		}

		self::$shutdownRegistered = true;
		register_shutdown_function(static function (): void {
			self::dump();
		});
	}

	public static function increment(string $counter, int $by = 1): void
	{
		self::$counters[$counter] += $by;
	}

	private static function dump(): void
	{
		if (array_sum(self::$counters) === 0) {
			return;
		}

		$lines = '';
		foreach (self::$counters as $name => $value) {
			$lines .= sprintf('%s=%d', $name, $value) . PHP_EOL;
		}

		$output = '[template-arguments stats]' . PHP_EOL . $lines;
		if (self::$outputFile !== null) {
			file_put_contents(self::$outputFile, $output, FILE_APPEND | LOCK_EX);
			return;
		}

		fwrite(STDERR, $output);
	}

}
