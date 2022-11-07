<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Issue;

use DateTimeImmutable;

class IssueCache
{

	/**
	 * @param array<int, Issue> $issues
	 */
	public function __construct(private DateTimeImmutable $date, private array $issues)
	{
	}

	public function getDate(): DateTimeImmutable
	{
		return $this->date;
	}

	/**
	 * @return array<int, Issue>
	 */
	public function getIssues(): array
	{
		return $this->issues;
	}

}
