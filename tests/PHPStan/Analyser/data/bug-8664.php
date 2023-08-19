<?php

declare(strict_types=1);

namespace Bug8664;

class UserObject
{
	protected ?int $id = null;
	protected ?string $username = null;

	public function setId(?int $id = null): void
	{
		$this->id = $id;
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setUsername(?string $username = null): void
	{
		$this->username = $username;
	}

	public function getUsername(): ?string
	{
		return $this->username;
	}
}

class DataObject
{
	protected ?UserObject $user = null;

	public function setUser(?UserObject $user = null): void
	{
		$this->user = $user;
	}

	public function getUser(): ?UserObject
	{
		return $this->user;
	}
}

class Test
{
	public function test(): void
	{
		$data = new DataObject();

		$userObject = $data->getUser();

		if ($userObject?->getId() > 0) {
			$userId = $userObject->getId();

			var_dump($userId);
		}

		if (null !== $userObject?->getUsername()) {
			$userUsername = $userObject->getUsername();

			var_dump($userUsername);
		}
	}
}
