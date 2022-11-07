<?php declare(strict_types = 1);

namespace PHPStan\IssueBot\Playground;

class PlaygroundExample
{

	/** @var list<string> */
	private array $users;

	public function __construct(
		private string $url,
		private string $hash,
		string $user,
	)
	{
		$this->users = [$user];
	}

	public function getUrl(): string
	{
		return $this->url;
	}

	public function getHash(): string
	{
		return $this->hash;
	}

	public function addUser(string $user): void
	{
		$this->users[] = $user;
	}

	/**
	 * @return list<string>
	 */
	public function getUsers(): array
	{
		return $this->users;
	}

}
