<?php declare(strict_types = 1);

namespace PHPStan\Parallel;

class Schedule
{

	/** @var int */
	private $numberOfProcesses;

	/** @var array<array<string>> */
	private $jobs;

	/**
	 * @param int $numberOfProcesses
	 * @param array<array<string>> $jobs
	 */
	public function __construct(int $numberOfProcesses, array $jobs)
	{
		$this->numberOfProcesses = $numberOfProcesses;
		$this->jobs = $jobs;
	}

	public function getNumberOfProcesses(): int
	{
		return $this->numberOfProcesses;
	}

	/**
	 * @return array<array<string>>
	 */
	public function getJobs(): array
	{
		return $this->jobs;
	}

}
