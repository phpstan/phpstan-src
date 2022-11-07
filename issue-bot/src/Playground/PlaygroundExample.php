<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

class PlaygroundExample
{

	public function __construct(
		private string $url,
		private string $hash,
	)
	{
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getHash(): string
	{
		return $this->hash;
	}

}
