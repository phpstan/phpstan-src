<?php declare(strict_types = 1);

namespace PHPStan\Benchmark;

use LogicException;
use PHPStan\File\ParentDirectoryRelativePathHelper;
use Tally\Attribute\Before;
use Tally\Attribute\DataProvider;
use Tally\Attribute\Linear;
use Tally\ScratchDirectory;
use function implode;
use function range;

final class ParentDirectoryRelativePathHelperBench
{

	private ?ParentDirectoryRelativePathHelper $helper = null;

	private string $file = '';

	public function __construct(private ScratchDirectory $scratch)
	{
	}

	/**
	 * @return iterable<string, array{int}>
	 */
	public static function depths(): iterable
	{
		foreach ([4, 16, 64] as $depth) {
			yield 'depth-' . $depth => [$depth];
		}
	}

	#[Before]
	public function boot(int $depth): void
	{
		BenchContainer::create($this->scratch);
		// the working directory and the file share $depth directories
		$directory = '/' . implode('/', range(1, $depth));
		$this->helper = new ParentDirectoryRelativePathHelper($directory . '/project');
		$this->file = $directory . '/src/Foo.php';
		$this->helper->getRelativePath('/warmup/src/Foo.php');
	}

	#[DataProvider(method: 'depths')]
	#[Linear(size: 'depth')]
	public function benchGetRelativePath(int $depth): string
	{
		if ($this->helper === null) {
			throw new LogicException('boot() did not run.');
		}

		return $this->helper->getRelativePath($this->file);
	}

}
