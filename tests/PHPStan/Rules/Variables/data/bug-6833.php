<?php // lint >= 8.0

declare(strict_types=1);

namespace Bug6833;

use PHPStan\TrinaryLogic;
use function PHPStan\Testing\assertVariableCertainty;

class File
{
	public function __construct(private int $id) {}
	public function getId(): int { return $this->id; }
}

/**
 * @implements \IteratorAggregate<int, File>
 */
class FileCollection implements \IteratorAggregate
{
	/** @var File[] */
	private array $files = [];

	public function add(File $file): void
	{
		$this->files[] = $file;
	}

	/** @throws void */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->files);
	}
}

function testThrowsVoidOnGetIterator(FileCollection $files): void
{
	try {
		foreach ($files as $file) {
			echo $file->getId();
		}
	} catch (\Throwable) {
		assertVariableCertainty(TrinaryLogic::createYes(), $file);
		echo 'Invalid file:' . $file->getId();
	}
}

/**
 * @implements \IteratorAggregate<int, File>
 */
class FileCollectionWithoutThrowsVoid implements \IteratorAggregate
{
	/** @var File[] */
	private array $files = [];

	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->files);
	}
}

function testWithoutThrowsVoid(FileCollectionWithoutThrowsVoid $files): void
{
	try {
		foreach ($files as $file) {
			echo $file->getId();
		}
	} catch (\Throwable) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $file);
		echo $file->getId(); // error - getIterator() could throw
	}
}

/**
 * @implements \IteratorAggregate<int, File>
 */
class FileCollectionExplicitThrows implements \IteratorAggregate
{
	/** @var File[] */
	private array $files = [];

	/** @throws \RuntimeException */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->files);
	}
}

function testExplicitThrowsMatchingCatch(FileCollectionExplicitThrows $files): void
{
	try {
		foreach ($files as $file) {
			echo $file->getId();
		}
	} catch (\Throwable) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $file);
		echo $file->getId(); // error - getIterator() can throw RuntimeException
	}
}

function testExplicitThrowsNonMatchingCatch(FileCollectionExplicitThrows $files): void
{
	try {
		foreach ($files as $file) {
			if ($file->getId() < 0) {
				throw new \LogicException('negative');
			}
		}
	} catch (\LogicException) {
		echo $file->getId(); // no error - RuntimeException doesn't match LogicException catch
	}
}

/** @param File[] $files */
function testArrayForeach(array $files): void
{
	try {
		foreach ($files as $file) {
			echo $file->getId();
		}
	} catch (\Throwable) {
		assertVariableCertainty(TrinaryLogic::createYes(), $file);
		echo $file->getId(); // no error - arrays don't call getIterator()
	}
}

function testThrowsVoidFinallyScope(FileCollection $files): void
{
	try {
		foreach ($files as $file) {
			doSomething();
		}
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $file);
	}
}

function doSomething(): void {}
