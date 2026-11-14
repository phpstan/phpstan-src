<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use Nette\Neon\Neon;
use PHPStan\Analyser\Error;
use PHPStan\Analyser\Ignore\IgnoredErrorHelper;
use PHPStan\Analyser\Ignore\IgnoredErrorHelperProcessedResult;
use PHPStan\Analyser\Ignore\IgnoredErrorHelperResult;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\ExpectedFor;
use Tally\ScratchDirectory;
use function file_put_contents;

final class IgnoredErrorHelperBench
{

	private const ENTRIES = 32;

	private const ERRORS = 64;

	private ?IgnoredErrorHelperResult $result = null;

	/** @var list<Error> */
	private array $errors = [];

	/** @var list<string> */
	private array $files = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int, bool}>
	 */
	public static function ignoreErrors(): iterable
	{
		// entries with the identifier of the errors and several paths each, whose message does not match
		yield 'paths-8' => [8, false];
		// entries with other identifiers than the errors
		yield 'other-identifiers' => [1, true];
	}

	#[Before]
	public function boot(int $paths, bool $otherIdentifiers): void
	{
		$entries = [];
		for ($i = 0; $i < self::ENTRIES; $i++) {
			$entryPaths = [];
			for ($j = 0; $j < $paths; $j++) {
				$entryPaths[] = '*/ignored' . $j . '/*';
			}
			$entries[] = [
				'message' => '#^Never reported ' . $i . '$#',
				'identifier' => $otherIdentifiers ? 'bench.other' . $i : 'bench.reported',
				'paths' => $entryPaths,
			];
		}

		$config = $this->scratch->path() . '/ignore-errors.neon';
		file_put_contents($config, Neon::encode([
			'parameters' => [
				'ignoreErrors' => $entries,
				'reportUnmatchedIgnoredErrors' => false,
			],
		], true));

		$container = BenchContainer::create($this->scratch, [$config]);
		$this->result = $container->getByType(IgnoredErrorHelper::class)->initialize();

		for ($i = 0; $i < 8; $i++) {
			$this->files[] = '/bench/src/File' . $i . '.php';
		}
		for ($i = 0; $i < self::ERRORS; $i++) {
			$file = $this->files[$i % 8];
			$this->errors[] = new Error('Reported ' . $i, $file, $i + 1, true, $file, null, null, null, null, 'bench.reported');
		}

		$this->result->process([$this->errors[0]], false, [$this->files[0]], false);
	}

	/**
	 * Each error is matched against each ignoreErrors entry.
	 */
	#[DataProvider(method: 'ignoreErrors')]
	#[ExpectedFor(dataSet: 'other-identifiers', instructions: '227.2K', cycles: '417.5K', memory: '3.438KiB')]
	#[ExpectedFor(dataSet: 'paths-8', instructions: '21.83M', cycles: '29.11M', memory: '5.750KiB')]
	public function benchProcess(int $paths, bool $otherIdentifiers): IgnoredErrorHelperProcessedResult
	{
		if ($this->result === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->result->process($this->errors, false, $this->files, false);
	}

}
