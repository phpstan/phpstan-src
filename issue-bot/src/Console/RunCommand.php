<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Console;

use Exception;
use Fidry\CpuCoreCounter\CpuCoreCounter as FidryCpuCoreCounter;
use Fidry\CpuCoreCounter\NumberOfCpuCoreNotFound;
use Nette\Neon\Neon;
use Nette\Utils\Json;
use PHPStan\IssueBot\Playground\PlaygroundCache;
use PHPStan\IssueBot\Playground\PlaygroundError;
use PHPStan\IssueBot\Playground\PlaygroundResult;
use PHPStan\IssueBot\Process\ProcessPromise;
use React\EventLoop\LoopInterface;
use React\EventLoop\StreamSelectLoop;
use React\Promise\Deferred;
use React\Promise\PromiseInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Throwable;
use function array_key_exists;
use function array_values;
use function count;
use function escapeshellarg;
use function explode;
use function file_get_contents;
use function file_put_contents;
use function implode;
use function is_file;
use function ksort;
use function microtime;
use function mkdir;
use function React\Promise\set_rejection_handler;
use function serialize;
use function sha1;
use function sprintf;
use function str_replace;
use function strpos;
use function sys_get_temp_dir;
use function unserialize;

class RunCommand extends Command
{

	public function __construct(private string $playgroundCachePath, private string $tmpDir)
	{
		parent::__construct();
	}

	protected function configure(): void
	{
		$this->setName('run');
		$this->addArgument('phpVersion', InputArgument::REQUIRED);
		$this->addArgument('playgroundHashes', InputArgument::REQUIRED);
	}

	protected function execute(InputInterface $input, OutputInterface $output): int
	{
		$phpVersion = (int) $input->getArgument('phpVersion');
		$commaSeparatedPlaygroundHashes = $input->getArgument('playgroundHashes');
		$playgroundHashes = explode(',', $commaSeparatedPlaygroundHashes);
		$playgroundCache = $this->loadPlaygroundCache();

		try {
			$cpuCount = (new FidryCpuCoreCounter())->getCount();
		} catch (NumberOfCpuCoreNotFound) {
			$cpuCount = 1;
		}

		$loop = new StreamSelectLoop();
		$jobs = [];
		foreach ($playgroundHashes as $hash) {
			if (!array_key_exists($hash, $playgroundCache->getResults())) {
				throw new Exception(sprintf('Hash %s must exist', $hash));
			}
			$result = $playgroundCache->getResults()[$hash];

			// written once here because both analyses of the same snippet run concurrently
			$writeSuccess = file_put_contents($this->getCodePath($hash), $result->getCode());
			if ($writeSuccess === false) {
				throw new Exception('Code write unsuccessful');
			}

			$jobs[] = [$phpVersion, $hash, $result, false];
			if ($result->isBleedingEdge()) {
				continue;
			}

			// also analyse with bleeding edge to find out whether it already fixes the issue
			$jobs[] = [$phpVersion, $hash, $result, true];
		}

		$allErrors = [];
		$allBleedingEdgeErrors = [];

		set_rejection_handler(static function (Throwable $t): void {
			throw $t;
		});

		$this->runPool($jobs, $cpuCount, function (array $job) use ($output, $loop): PromiseInterface {
			[$phpVersion, $hash, $result, $isAdditionalBleedingEdgeRun] = $job;
			return $this->analyseHash($loop, $output, $phpVersion, $result, $isAdditionalBleedingEdgeRun)->then(
				static fn (array $errors) => [$hash, $errors, $isAdditionalBleedingEdgeRun],
			);
		})->then(static function (array $results) use (&$allErrors, &$allBleedingEdgeErrors): void {
			foreach ($results as [$hash, $errors, $isAdditionalBleedingEdgeRun]) {
				if ($isAdditionalBleedingEdgeRun) {
					$allBleedingEdgeErrors[$hash] = $errors;
					continue;
				}
				$allErrors[$hash] = $errors;
			}
		});

		$loop->run();

		$data = ['phpVersion' => $phpVersion, 'errors' => $allErrors, 'bleedingEdgeErrors' => $allBleedingEdgeErrors];

		$writeSuccess = file_put_contents(
			sprintf($this->tmpDir . '/results-%d-%s.tmp', $phpVersion, sha1($commaSeparatedPlaygroundHashes)),
			serialize($data),
		);
		if ($writeSuccess === false) {
			throw new Exception('Result write unsuccessful');
		}

		return 0;
	}

	/**
	 * @param array<array{int, string, PlaygroundResult, bool}> $jobs
	 * @param callable(array{int, string, PlaygroundResult, bool}): PromiseInterface<array{string, list<PlaygroundError>, bool}> $jobRunner
	 * @return PromiseInterface<list<array{string, list<PlaygroundError>, bool}>>
	 */
	private function runPool(array $jobs, int $concurrency, callable $jobRunner): PromiseInterface
	{
		$deferred = new Deferred();
		$results = [];
		$pending = 0;
		$index = 0;
		$total = count($jobs);
		$rejected = false;

		if ($total === 0) {
			$deferred->resolve([]);
			return $deferred->promise();
		}

		$runNext = static function () use (&$runNext, &$jobs, &$results, &$pending, &$index, &$rejected, $total, $concurrency, $jobRunner, $deferred): void {
			if ($rejected) {
				return;
			}
			while ($pending < $concurrency && $index < $total) {
				$currentIndex = $index++;
				$pending++;

				$jobRunner($jobs[$currentIndex])->then(
					static function ($result) use (&$results, &$pending, $currentIndex, $total, $runNext, $deferred): void {
						$results[$currentIndex] = $result;
						$pending--;

						if (count($results) === $total) {
							ksort($results);
							$deferred->resolve(array_values($results));
						} else {
							$runNext();
						}
					},
					static function ($error) use (&$rejected, $deferred): void {
						if ($rejected) {
							return;
						}
						$rejected = true;
						$deferred->reject($error);
					},
				);
			}
		};

		$runNext();

		return $deferred->promise();
	}

	/**
	 * @return PromiseInterface<list<PlaygroundError>>
	 */
	private function analyseHash(LoopInterface $loop, OutputInterface $output, int $phpVersion, PlaygroundResult $result, bool $isAdditionalBleedingEdgeRun): PromiseInterface
	{
		$configFiles = [
			__DIR__ . '/../../playground.neon',
			__DIR__ . '/../../vendor/phpstan/phpstan-deprecation-rules/rules.neon',
		];
		if ($result->isBleedingEdge() || $isAdditionalBleedingEdgeRun) {
			$configFiles[] = __DIR__ . '/../../../conf/bleedingEdge.neon';
		}
		if ($result->isStrictRules()) {
			$configFiles[] = __DIR__ . '/../../vendor/phpstan/phpstan-strict-rules/rules.neon';
		}
		// the additional bleeding edge run of the same snippet runs concurrently, it needs its own files
		$runSuffix = $isAdditionalBleedingEdgeRun ? '-bleeding-edge' : '';
		$tmpDir = sys_get_temp_dir() . '/phpstan-issue-bot-' . $result->getHash() . $runSuffix;
		@mkdir($tmpDir, 0777, true);

		$options = $result->getOptions();
		$parameters = [
			'level' => $result->getLevel(),
			'inferPrivatePropertyTypeFromConstructor' => $options['inferPrivatePropertyTypeFromConstructor'] ?? true,
			'treatPhpDocTypesAsCertain' => $result->isTreatPhpDocTypesAsCertain(),
			'phpVersion' => $phpVersion,
			'tmpDir' => $tmpDir,
			'rememberPossiblyImpureFunctionValues' => $options['rememberPossiblyImpureFunctionValues'] ?? true,
			'checkBenevolentUnionTypes' => $options['checkBenevolentUnionTypes'] ?? false,
			'checkTooWideReturnTypesInProtectedAndPublicMethods' => $options['checkTooWideTypesInProtectedAndPublicMethods'] ?? false,
			'checkTooWideParameterOutInProtectedAndPublicMethods' => $options['checkTooWideTypesInProtectedAndPublicMethods'] ?? false,
			'checkTooWideThrowTypesInProtectedAndPublicMethods' => $options['checkTooWideTypesInProtectedAndPublicMethods'] ?? false,
			'reportUnsafeArrayStringKeyCasting' => $options['reportUnsafeArrayStringKeyCasting'] ?? null,
		];
		$parameters['exceptions'] = [
			'implicitThrows' => $options['implicitThrows'] ?? true,
			'reportUncheckedExceptionDeadCatch' => $options['reportUncheckedExceptionDeadCatch'] ?? true,
			'uncheckedExceptionClasses' => $options['uncheckedExceptionClasses'] ?? [],
			'checkedExceptionClasses' => $options['checkedExceptionClasses'] ?? [],
			'check' => [
				'missingCheckedExceptionInThrows' => $options['missingCheckedExceptionInThrows'] ?? false,
				'tooWideImplicitThrowType' => $options['tooWideImplicitThrowType'] ?? false,
			],
		];

		$neon = Neon::encode([
			'includes' => $configFiles,
			'parameters' => $parameters,
		]);

		$hash = $result->getHash();
		$neonPath = sprintf($this->tmpDir . '/%s%s.neon', $hash, $runSuffix);
		$codePath = $this->getCodePath($hash);
		file_put_contents($neonPath, $neon);

		$commandArray = [
			escapeshellarg(__DIR__ . '/../../../bin/phpstan'),
			'analyse',
			'--error-format',
			'json',
			'--no-progress',
			'-c',
			escapeshellarg($neonPath),
			escapeshellarg($codePath),
		];

		$output->writeln(sprintf('Starting analysis of %s%s', $hash, $runSuffix));
		$process = new ProcessPromise($loop, implode(' ', $commandArray));
		$startTime = microtime(true);
		return $process->run()->then(static function (string $stdout) use ($hash, $runSuffix, $output, $startTime) {
			try {
				$json = Json::decode($stdout, forceArrays: true);
			} catch (Throwable $e) {
				$output->writeln($stdout);
				throw new Exception(sprintf('Failed to decode JSON for %s: %s', $hash, $e->getMessage()));
			}

			$errors = self::extractErrors($json, $hash);

			$elapsedTime = microtime(true) - $startTime;
			$output->writeln(sprintf('Analysis of %s%s took %.2f s', $hash, $runSuffix, $elapsedTime));

			return $errors;
		});
	}

	/**
	 * @param mixed[] $json
	 * @return list<PlaygroundError>
	 */
	public static function extractErrors(array $json, string $hash): array
	{
		$errors = [];
		foreach ($json['files'] ?? [] as $file) {
			foreach ($file['messages'] as $message) {
				$messageText = str_replace(sprintf('/%s.php', $hash), '/tmp.php', $message['message']);
				if (strpos($messageText, 'Internal error') !== false) {
					throw new Exception(sprintf('While analysing %s: %s', $hash, $messageText));
				}
				$errors[] = new PlaygroundError($message['line'] ?? -1, $messageText, $message['identifier'] ?? null, $message['fixable'] ?? false);
			}
		}

		// Top-level (non-file) errors: a parallel worker crashing (e.g. out of
		// memory), a bootstrap failure, etc. PHPStan reports these in $json['errors']
		// with file_errors === 0, so without recording them here the analysis looks
		// like it produced zero errors and any expected output silently disappears
		// from the diff. Record them so they are visible.
		foreach ($json['errors'] ?? [] as $genericError) {
			$messageText = str_replace(sprintf('/%s.php', $hash), '/tmp.php', (string) $genericError);
			$errors[] = new PlaygroundError(-1, $messageText, null);
		}

		return $errors;
	}

	private function getCodePath(string $hash): string
	{
		return sprintf($this->tmpDir . '/%s.php', $hash);
	}

	private function loadPlaygroundCache(): PlaygroundCache
	{
		if (!is_file($this->playgroundCachePath)) {
			throw new Exception('Playground cache must exist');
		}

		$contents = file_get_contents($this->playgroundCachePath);
		if ($contents === false) {
			throw new Exception('Read unsuccessful');
		}

		return unserialize($contents);
	}

}
