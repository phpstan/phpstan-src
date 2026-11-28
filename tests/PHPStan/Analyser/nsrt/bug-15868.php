<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug15868;

use function PHPStan\Testing\assertType;

final class Finder
{
	/** @return list<int> */
	public function find(?int $id): array
	{
		return [];
	}

	public function reset(?int &$id): void
	{
		$id = null;
	}
}

final class Page
{
	private ?int $id = null;

	public function __construct(private Finder $finder, private ?Finder $maybeFinder)
	{
	}

	/** @return list<int> */
	private function load(): array
	{
		return $this->finder->find($this->id);
	}

	/** @return list<int> */
	private function loadNullsafe(): array
	{
		return $this->maybeFinder?->find($this->id) ?? [];
	}

	private function reset(): void
	{
		$this->finder->reset($this->id);
	}

	public function viaHelper(string $s): void
	{
		$this->id = (int) $s;
		$this->load();
		assertType('int', $this->id);
	}

	public function viaNullsafeHelper(string $s): void
	{
		$this->id = (int) $s;
		$this->loadNullsafe();
		assertType('int', $this->id);
	}

	public function viaByRefHelper(string $s): void
	{
		$this->id = (int) $s;
		$this->reset();
		assertType('int|null', $this->id);
	}

	public function inline(string $s): void
	{
		$this->id = (int) $s;
		$this->finder->find($this->id);
		assertType('int', $this->id);
	}
}
