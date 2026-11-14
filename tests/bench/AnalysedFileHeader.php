<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use Tally\Expectation\CodeEdit;
use Tally\Expectation\ExpectationException;
use Tally\Metric;
use Tally\Metrics;
use Tally\Quantity\InvalidQuantityException;
use function preg_match;
use function sprintf;
use function strlen;

/**
 * Reads and writes the expected numbers in the comment after the first line of a benchmark in data/:
 *
 *     <?php declare(strict_types = 1);
 *
 *     /*
 *      * instructions: 3.119B
 *      * cycles: 3.398B
 *      * memory: 4.520MiB
 *      *\/
 *
 * Takes code and returns the edit of it, AnalysedFileExpectations reads the file.
 */
final class AnalysedFileHeader
{

	/** The line the comment starts on */
	public const LINE = 3;

	private const PATTERN = '~\A(<\?php[^\n]*\n)\n/\*\n \* instructions: (\S+)\n \* cycles: (\S+)\n \* memory: (\S+)\n \*/\n~';

	/**
	 * @throws InvalidQuantityException
	 */
	public function read(string $code): ?Metrics
	{
		if (preg_match(self::PATTERN, $code, $matches) !== 1) {
			return null;
		}

		return Metrics::parse($matches[2], $matches[3], $matches[4]);
	}

	/**
	 * Replaces the comment, or adds it after the <?php line.
	 *
	 * @throws ExpectationException
	 */
	public function write(string $code, Metrics $metrics): CodeEdit
	{
		$header = sprintf(
			"/*\n * instructions: %s\n * cycles: %s\n * memory: %s\n */\n",
			$this->format($metrics, Metric::Instructions),
			$this->format($metrics, Metric::Cycles),
			$this->format($metrics, Metric::Memory),
		);
		if (preg_match(self::PATTERN, $code, $matches) === 1) {
			return new CodeEdit(strlen($matches[1]), strlen($matches[0]) - strlen($matches[1]), "\n" . $header);
		}
		if (preg_match('~\A<\?php[^\n]*\n~', $code, $matches) !== 1) {
			throw new ExpectationException('The benchmark does not start with a <?php line.');
		}

		return new CodeEdit(strlen($matches[0]), 0, "\n" . $header);
	}

	private function format(Metrics $metrics, Metric $metric): string
	{
		return $metric->format()->format($metrics->get($metric) ?? 0);
	}

}
