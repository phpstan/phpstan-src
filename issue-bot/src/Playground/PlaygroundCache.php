<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

class PlaygroundCache
{

	/**
	 * @param array<string, PlaygroundResult> $results
	 */
	public function __construct(private array $results)
	{
	}

	/**
	 * @return array<string, PlaygroundResult>
	 */
	public function getResults(): array
	{
		return $this->results;
	}

}
