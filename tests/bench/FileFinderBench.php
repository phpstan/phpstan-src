<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\File\DirectoryWalker;
use PHPStan\File\FileExcluder;
use PHPStan\File\FileFinder;
use PHPStan\File\FileFinderResult;
use PHPStan\File\FileHelper;
use Tally\Attribute\Before;
use Tally\ScratchDirectory;
use function class_exists;
use function is_dir;
use function mkdir;
use function touch;

final class FileFinderBench
{

	private const FILES = 256;

	private ?FileFinder $finder = null;

	/** @var list<string> */
	private array $paths = [];

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	#[Before]
	public function boot(): void
	{
		$container = BenchContainer::create($this->scratch);
		$directory = $this->scratch->path() . '/file-finder-bench';
		foreach ([$directory, $directory . '/src', $directory . '/excluded'] as $dir) {
			if (is_dir($dir)) {
				continue;
			}

			mkdir($dir);
		}

		// the paths given on the command line, half of them in an excluded directory
		$this->paths = [];
		for ($i = 0; $i < self::FILES; $i++) {
			$path = $directory . '/' . ($i % 2 === 0 ? 'src' : 'excluded') . '/File' . $i . '.php';
			touch($path);
			$this->paths[] = $path;
		}

		$fileHelper = new FileHelper($directory);
		$excluder = new FileExcluder($fileHelper, [$directory . '/excluded']);
		$this->finder = class_exists(DirectoryWalker::class)
			? new FileFinder($excluder, $fileHelper, ['php'], $container->getByType(DirectoryWalker::class))
			// @phpstan-ignore arguments.count (before 2.2.14)
			: new FileFinder($excluder, $fileHelper, ['php']);
		$this->finder->findFiles([$this->paths[0], $this->paths[1]]);
	}

	public function benchFindFiles(): FileFinderResult
	{
		if ($this->finder === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->finder->findFiles($this->paths);
	}

}
