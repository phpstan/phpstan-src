<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug6833Nsrt;

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
class FileCollectionThrowsVoid implements \IteratorAggregate
{
	/** @throws void */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator([]);
	}
}

/**
 * @implements \IteratorAggregate<int, File>
 */
class FileCollectionNoAnnotation implements \IteratorAggregate
{
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator([]);
	}
}

/**
 * @implements \IteratorAggregate<int, File>
 */
class FileCollectionExplicitThrows implements \IteratorAggregate
{
	/** @throws \RuntimeException */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator([]);
	}
}

function testThrowsVoidCatchScope(FileCollectionThrowsVoid $files): void
{
	try {
		foreach ($files as $file) {
			doSomething();
		}
	} catch (\Throwable) {
		assertVariableCertainty(TrinaryLogic::createYes(), $file);
	}
}

function testNoAnnotationCatchScope(FileCollectionNoAnnotation $files): void
{
	try {
		foreach ($files as $file) {
			doSomething();
		}
	} catch (\Throwable) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $file);
	}
}

function testExplicitThrowsCatchScope(FileCollectionExplicitThrows $files): void
{
	try {
		foreach ($files as $file) {
			doSomething();
		}
	} catch (\Throwable) {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $file);
	}
}

function testThrowsVoidFinallyScope(FileCollectionThrowsVoid $files): void
{
	try {
		foreach ($files as $file) {
			doSomething();
		}
	} finally {
		assertVariableCertainty(TrinaryLogic::createMaybe(), $file);
	}
}

/** @param File[] $files */
function testArrayCatchScope(array $files): void
{
	try {
		foreach ($files as $file) {
			doSomething();
		}
	} catch (\Throwable) {
		assertVariableCertainty(TrinaryLogic::createYes(), $file);
	}
}

function doSomething(): void {}
