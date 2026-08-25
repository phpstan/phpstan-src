<?php declare(strict_types = 1);

namespace PHPStan\Command;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\DependencyInjection\Container;
use function array_merge;
use function PHPStan\collectNewAutoloadFunctions;
use function spl_autoload_functions;

/**
 * Executes the configured bootstrapFiles, once per process.
 *
 * The analyse flow calls run() at exactly the places that need the files
 * executed - resources they open (an objectManagerLoader's database
 * connection, a query reflector's PDO) must not be inherited by forked
 * children:
 *
 * - the main thread right before an in-process analysis (AnalyserRunner),
 * - every worker, spawned or forked (WorkerRunner),
 * - the main thread after the workers of a parallel analysis
 *   (AnalyserRunner), and after a fully cached run (AnalyseApplication) -
 *   before the phases that may reflect analysed code.
 *
 * Every other command runs them eagerly in CommandHelper::begin(). The
 * once-per-process latch exists for a worker forked from a parent that
 * already ran the files (the fixer flow runs them eagerly before its
 * repeated analysis rounds): such a worker inherits that execution and
 * run() must not repeat it.
 */
#[AutowiredService]
final class BootstrapFilesRunner
{

	private bool $hasRun = false;

	public function __construct(private Container $container)
	{
	}

	/**
	 * @throws InceptionNotSuccessfulException
	 */
	public function run(Output $errorOutput, bool $debugEnabled): void
	{
		if ($this->hasRun) {
			return;
		}
		$this->hasRun = true;

		/** @var list<callable(string): void>|false $autoloadFunctionsBefore */
		$autoloadFunctionsBefore = spl_autoload_functions();

		foreach ($this->container->getParameter('bootstrapFiles') as $bootstrapFile) {
			CommandHelper::executeBootstrapFile($bootstrapFile, $this->container, $errorOutput, $debugEnabled);
		}

		self::mergeNewAutoloadFunctions($autoloadFunctionsBefore);
	}

	/**
	 * Merges autoloaders registered since $autoloadFunctionsBefore into the
	 * globals the BetterReflection source locators consult lazily (see
	 * autoloadFunctions.php) - late merging in a deferred or forked run is
	 * picked up by the next reflection ask.
	 *
	 * @param list<callable(string): void>|false $autoloadFunctionsBefore
	 */
	public static function mergeNewAutoloadFunctions(array|false $autoloadFunctionsBefore): void
	{
		/** @var list<callable(string): void>|false $autoloadFunctionsAfter */
		$autoloadFunctionsAfter = spl_autoload_functions();
		if ($autoloadFunctionsBefore === false || $autoloadFunctionsAfter === false) {
			return;
		}

		$collectedAutoloadFunctions = collectNewAutoloadFunctions($autoloadFunctionsBefore, $autoloadFunctionsAfter);
		$GLOBALS['__phpstanAutoloadFunctions'] = array_merge(
			$GLOBALS['__phpstanAutoloadFunctions'] ?? [],
			$collectedAutoloadFunctions['appended'],
		);
		$GLOBALS['__phpstanAutoloadFunctionsPrependedToComposer'] = array_merge(
			$GLOBALS['__phpstanAutoloadFunctionsPrependedToComposer'] ?? [],
			$collectedAutoloadFunctions['prepended'],
		);
	}

}
