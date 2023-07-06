<?php declare(strict_types = 1);

namespace PHPStan\File;

use Closure;
use Iterator;
use IteratorAggregate;
use RecursiveIteratorIterator;
use ReturnTypeWillChange;
use SplFileInfo;
use Symfony\Component\Finder\Iterator\ExcludeDirectoryFilterIterator;
use Symfony\Component\Finder\Iterator\FilenameFilterIterator;
use Symfony\Component\Finder\Iterator\FileTypeFilterIterator;
use Symfony\Component\Finder\Iterator\RecursiveDirectoryIterator;
use function rtrim;
use const DIRECTORY_SEPARATOR;

/**
 * Backport Symfony Finder with early directory prunning for \PHPStan\File\FileFinder.
 *
 * Remove this class once Symfony 6.4 with https://github.com/symfony/symfony/pull/50877 optimization
 * is released and phpstan dependencies allows it.
 *
 * @implements IteratorAggregate<string, SplFileInfo>
 *
 * @internal
 */
class FileFinderSymfonyBackport implements IteratorAggregate
{

	private string $in;

	private string $name;

	/** @var Closure(SplFileInfo): bool  */
	private Closure $pruneFilter;

	public function __construct()
	{
		// ignore "Class PHPStan\File\FileFinderSymfonyBackport has an uninitialized
		// property $xxx. Give it default value or assign it in the constructor."
		$this->in = '';
		$this->name = '';
		$this->pruneFilter = static fn () => true;
	}

	/**
	 * @return $this
	 */
	public function in(string $dir)
	{
		$this->in = $this->normalizeDir($dir);

		return $this;
	}

	private function normalizeDir(string $dir): string
	{
		if ($dir === '/') {
			return $dir;
		}

		$dir = rtrim($dir, '/' . DIRECTORY_SEPARATOR);

		return $dir;
	}

	/**
	 * @return $this
	 */
	public function name(string $pattern)
	{
		$this->name = $pattern;

		return $this;
	}

	/**
	 * @return $this
	 */
	public function followLinks()
	{
		return $this;
	}

	/**
	 * @return $this
	 */
	public function files()
	{
		return $this;
	}

	/**
	 * @param Closure(SplFileInfo): bool $closure
	 * @param true $prune
	 *
	 * @return $this
	 */
	public function filter(Closure $closure, bool $prune)
	{
		$this->pruneFilter = $closure;

		return $this;
	}


	/**
	 * @return Iterator<string, SplFileInfo>
	 */
	#[ReturnTypeWillChange]
	public function getIterator(): Iterator
	{
		$iterator = new RecursiveDirectoryIterator($this->in, RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS);
		$iterator = new class($iterator, [$this->pruneFilter]) extends ExcludeDirectoryFilterIterator {

			/** @var list<callable(SplFileInfo): bool> */
			private array $pruneFilters = [];

			/**
			 * @param list<Closure(SplFileInfo): bool> $directories
			 */
			public function __construct(Iterator $iterator, array $directories)
			{
				parent::__construct($iterator, []);

				$this->pruneFilters = $directories;
			}

			public function accept(): bool
			{
				if ($this->hasChildren()) {
					foreach ($this->pruneFilters as $pruneFilter) {
						if (!$pruneFilter($this->current())) {
							return false;
						}
					}
				}

				return parent::accept();
			}

		};
		$iterator = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::SELF_FIRST);
		$iterator = new FileTypeFilterIterator($iterator, FileTypeFilterIterator::ONLY_FILES);
		$iterator = new FilenameFilterIterator($iterator, [$this->name], []);

		return $iterator;
	}

}
