<?php // lint >= 8.1

declare(strict_types = 1);

namespace Bug15823;

use function PHPStan\Testing\assertType;

class Repository
{
	private ?string $apiUrl = null;

	public function setApiUrl(string $apiUrl): void
	{
		$this->apiUrl = $apiUrl;
	}

	public function getApiUrl(): ?string
	{
		return $this->apiUrl;
	}
}

class Describer
{
	public function describe(Repository $repository): string
	{
		return 'foo';
	}
}

class Client
{
	public function __construct(
		private readonly Repository $repository,
		private readonly Describer $describer,
	)
	{
	}

	public function fetch(): void
	{
		if ($this->repository->getApiUrl() !== null) {
			$handler = $this->errorHandler();
			assertType('string', $this->repository->getApiUrl());
		}
	}

	private function errorHandler(): \Closure
	{
		return function (): string {
			return $this->describer->describe($this->repository);
		};
	}
}


class ConstructorAssigned
{
	private readonly Repository $repository;

	public function __construct(private readonly Describer $describer)
	{
		$this->repository = new Repository();
	}

	public function fetch(): void
	{
		if ($this->repository->getApiUrl() !== null) {
			$handler = $this->errorHandler();
			assertType('string', $this->repository->getApiUrl());
		}
	}

	private function errorHandler(): \Closure
	{
		return function (): string {
			return $this->describer->describe($this->repository);
		};
	}
}

class NotInitializedByConstructor
{
	private readonly Repository $repository;

	public function __construct(private readonly Describer $describer)
	{
	}

	public function fetch(): void
	{
		if ($this->repository->getApiUrl() !== null) {
			$this->init();
			assertType('string|null', $this->repository->getApiUrl());
		}
	}

	private function init(): void
	{
		$this->describer->describe($this->repository);
	}
}
