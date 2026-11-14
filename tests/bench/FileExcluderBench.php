<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileHelper;
use Tally\Attribute\Before;
use Tally\Attribute\Constant;
use Tally\Attribute\DataProvider;
use Tally\ScratchDirectory;
use function is_dir;
use function mkdir;
use function touch;

final class FileExcluderBench
{

	private ?FileExcluder $excluder = null;

	private string $file = '';

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function excludedFiles(): iterable
	{
		foreach ([16, 128, 1024] as $files) {
			yield 'files-' . $files => [$files];
		}
	}

	#[Before]
	public function boot(int $files): void
	{
		BenchContainer::create($this->scratch);
		$directory = $this->scratch->path() . '/file-excluder-bench';
		if (!is_dir($directory)) {
			mkdir($directory);
		}

		// excludes naming single files, as in excludePaths
		$excludes = [];
		for ($i = 0; $i < $files; $i++) {
			$excludes[] = $directory . '/Excluded' . $i . '.php';
			touch($directory . '/Excluded' . $i . '.php');
		}

		$this->excluder = new FileExcluder(new FileHelper($directory), $excludes);
		$this->file = $directory . '/Analysed.php';
		$this->excluder->isExcludedFromAnalysing($directory . '/Warmup.php');
	}

	/**
	 * Every found file is checked against the excludes.
	 */
	#[DataProvider(method: 'excludedFiles')]
	#[Constant(size: 'files')]
	public function benchIsNotExcluded(int $files): bool
	{
		if ($this->excluder === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->excluder->isExcludedFromAnalysing($this->file);
	}

}
