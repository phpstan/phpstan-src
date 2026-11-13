<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use Closure;
use LogicException;
use Nette\Neon\Neon;
use Nette\Utils\FileSystem;
use PHPStan\Analyser\Analyser;
use PHPStan\Analyser\AnalyserResultFinalizer;
use PHPStan\Command\CommandHelper;
use PHPStan\Command\InceptionNotSuccessfulException;
use Symfony\Component\Console\Input\ArrayInput;
use Symfony\Component\Console\Output\StreamOutput;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\DurationTolerance;
use Tally\DataSet;
use Tally\Quantity\InvalidQuantityException;
use Tally\ScratchDirectory;
use function basename;
use function count;
use function dirname;
use function error_reporting;
use function file_get_contents;
use function gc_disable;
use function glob;
use const E_ALL;
use const E_DEPRECATED;
use const STDERR;

/**
 * Analyses each file in data/ like bin/phpstan analyse does, with phpstan.neon. Only the analysis
 * of the file is counted: before it, PHPStan boots and analyses Warmup.php, so the code every
 * analysis goes through is loaded and compiled. The native run Tally starts before the counted
 * one compiles the container and fills the caches in the case's scratch directory, which the
 * counted run then reads.
 *
 * The expected numbers are in the comment at the top of each file in data/.
 *
 * The tolerance absorbs changes too small to notice in wall clock time, such as a small change in
 * code every analysis goes through, or a new PHP patch release on the runners: the expected count
 * is converted to milliseconds by how many of it the analysis runs per millisecond (the median
 * over the benchmarks, natively on PHP 8.5 and an Intel Xeon @ 2.60 GHz), and the quicker the
 * benchmark, the larger the share of it that may change.
 */
#[DurationTolerance(
	tolerances: [0 => 0.5, 100 => 0.25, 500 => 0.2, 2000 => 0.1],
	instructionsPerMillisecond: 5_800_000,
	cyclesPerMillisecond: 7_400_000,
	memory: 0.1,
)]
final class AnalysisBench
{

	private const WARMUP_FILE = __DIR__ . '/Warmup.php';

	/** @var (Closure(string): int)|null analyses a file and returns the number of errors */
	private ?Closure $analyse = null;

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, DataSet>
	 * @throws InvalidQuantityException
	 */
	public static function files(): iterable
	{
		$header = new AnalysedFileHeader();
		foreach (glob(__DIR__ . '/data/*.php') ?: [] as $file) {
			$name = basename($file);
			yield $name => DataSet::named($name, [$file])
				->withExpectations(new AnalysedFileExpectations($header, $file, $header->read((string) file_get_contents($file))));
		}
	}

	/**
	 * @throws InceptionNotSuccessfulException
	 */
	#[Before]
	public function boot(string $file): void
	{
		// like bin/phpstan
		error_reporting(E_ALL & ~E_DEPRECATED);
		gc_disable();

		$configFile = $this->scratch->path() . '/phpstan.neon';
		FileSystem::write($configFile, Neon::encode([
			'includes' => [__DIR__ . '/phpstan.neon'],
			'parameters' => ['tmpDir' => $this->scratch->path() . '/cache'],
		], true));

		$inceptionResult = CommandHelper::begin(
			new ArrayInput([]),
			new StreamOutput(STDERR),
			[self::WARMUP_FILE, $file],
			'-1',
			null,
			[dirname(__DIR__, 2)],
			$configFile,
			null,
			null,
			false,
			true,
			null,
			null,
			false,
		);
		// also tells the parser which files are analysed, the others are parsed without function bodies
		$inceptionResult->getFiles();
		$container = $inceptionResult->getContainer();
		$analyser = $container->getByType(Analyser::class);
		$finalizer = $container->getByType(AnalyserResultFinalizer::class);
		$this->analyse = static function (string $file) use ($analyser, $finalizer): int {
			$result = $analyser->analyse([$file], null, null, true, [$file]);

			return count($finalizer->finalize($result, false, true)->getErrors());
		};

		// A different file than the benchmark: PHPStan memoizes types and reflection in memory,
		// so analysing the benchmark twice would hide work from the counted analysis
		($this->analyse)(self::WARMUP_FILE);
	}

	#[DataProvider(method: 'files')]
	public function benchAnalyse(string $file): int
	{
		if ($this->analyse === null) {
			throw new LogicException('boot() did not run.');
		}

		return ($this->analyse)($file);
	}

}
