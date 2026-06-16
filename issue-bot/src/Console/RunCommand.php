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
		set_exception_handler(static function (\Throwable $e): void {
			fwrite(STDERR, 'Swallowed by global exception handler: ' . $e->getMessage() . "\n");
		});

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
			$jobs[] = [$phpVersion, $hash, $playgroundCache->getResults()[$hash]];
		}

		$allErrors = [];

		set_rejection_handler(static function (Throwable $t): void {
			throw $t;
		});

		$this->runPool($jobs, $cpuCount, function (array $job) use ($output, $loop): PromiseInterface {
			[$phpVersion, $hash, $result] = $job;
			return $this->analyseHash($loop, $output, $phpVersion, $result)->then(
				static fn (array $errors) => [$hash, $errors],
			);
		})->then(static function (array $results) use (&$allErrors): void {
			foreach ($results as [$hash, $errors]) {
				$allErrors[$hash] = $errors;
			}
		});

		$loop->run();

		$data = ['phpVersion' => $phpVersion, 'errors' => $allErrors];

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
	 * @param array<array{int, string, PlaygroundResult}> $jobs
	 * @param callable(array{int, string, PlaygroundResult}): PromiseInterface<array{string, list<PlaygroundError>}> $jobRunner
	 * @return PromiseInterface<list<array{string, list<PlaygroundError>}>>
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
							$deferred->resolve($results);
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
	private function analyseHash(LoopInterface $loop, OutputInterface $output, int $phpVersion, PlaygroundResult $result): PromiseInterface
	{
		$configFiles = [
			__DIR__ . '/../../playground.neon',
			__DIR__ . '/../../vendor/phpstan/phpstan-deprecation-rules/rules.neon',
		];
		if ($result->isBleedingEdge()) {
			$configFiles[] = __DIR__ . '/../../../conf/bleedingEdge.neon';
		}
		if ($result->isStrictRules()) {
			$configFiles[] = __DIR__ . '/../../vendor/phpstan/phpstan-strict-rules/rules.neon';
		}
		$tmpDir = sys_get_temp_dir() . '/phpstan-issue-bot-' . $result->getHash();
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
		$neonPath = sprintf($this->tmpDir . '/%s.neon', $hash);
		$codePath = sprintf($this->tmpDir . '/%s.php', $hash);
		file_put_contents($neonPath, $neon);
		file_put_contents($codePath, $result->getCode());

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

		$output->writeln(sprintf('Starting analysis of %s', $hash));
		$process = new ProcessPromise($loop, implode(' ', $commandArray));
		$startTime = microtime(true);
		return $process->run()->then(static function (string $stdout) use ($hash, $output, $startTime) {
			try {
				$json = Json::decode($stdout, Json::FORCE_ARRAY);
			} catch (Throwable $e) {
				echo $stdout . "\n";
				throw new Exception(sprintf('Failed to decode JSON for %s: %s', $hash, $e->getMessage()));
			}

			$errors = [];
			foreach ($json['files'] as ['messages' => $messages]) {
				foreach ($messages as $message) {
					$messageText = str_replace(sprintf('/%s.php', $hash), '/tmp.php', $message['message']);
					if (strpos($messageText, 'Internal error') !== false) {
						throw new Exception(sprintf('While analysing %s: %s', $hash, $messageText));
					}
					$errors[] = new PlaygroundError($message['line'] ?? -1, $messageText, $message['identifier'] ?? null);
				}
			}

			$elapsedTime = microtime(true) - $startTime;
			$output->writeln(sprintf('Analysis of %s took %.2f s', $hash, $elapsedTime));

			return $errors;
		});
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
