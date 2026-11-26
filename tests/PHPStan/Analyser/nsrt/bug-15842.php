<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15842;

use function PHPStan\Testing\assertType;

class Output {}

final class FinalOutput {}

interface Provider
{
	/** @return object|iterable<object>|null */
	public function provide(bool $one): object|array|null;
}

final class OutputProvider implements Provider
{
	#[\Override]
	public function provide(bool $one): iterable|Output
	{
		if ($one) {
			return new Output();
		}

		return [new Output()];
	}
}

final class FinalOutputProvider implements Provider
{
	#[\Override]
	public function provide(bool $one): iterable|FinalOutput
	{
		if ($one) {
			return new FinalOutput();
		}

		return [new FinalOutput()];
	}
}

interface IterableProvider
{
	/** @return iterable<object> */
	public function provide(): iterable;
}

final class ArrayOrTraversableProvider implements IterableProvider
{
	#[\Override]
	public function provide(): array|\Traversable
	{
		return [];
	}
}

function test(OutputProvider $p, FinalOutputProvider $f, ArrayOrTraversableProvider $a): void
{
	assertType('Bug15842\Output|iterable<object>', $p->provide(true));
	assertType('Bug15842\FinalOutput|iterable<object>', $f->provide(true));
	assertType('iterable<object>', $a->provide());
}
