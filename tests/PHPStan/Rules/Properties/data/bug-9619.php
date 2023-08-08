<?php // lint >= 7.4

namespace Bug9619;

interface User
{

	public function isLoggedIn(): bool;

}

class AdminPresenter
{
	/** @inject */
	public User $user;

	public function startup()
	{
		if (!$this->user->isLoggedIn()) {
			// do something
		}
	}
}

class AdminPresenter2
{
	private User $user;

	public function __construct(User $user)
	{
		$this->user = $user;
	}

	public function startup()
	{
		// do not report uninitialized property - it's initialized for sure
		if (!$this->user->isLoggedIn()) {
			// do something
		}
	}
}

class AdminPresenter3
{
	private \stdClass $user;

	public function startup()
	{
		$this->user = new \stdClass();
	}

	public function startup2()
	{
		// we cannot be sure which additional constructor gets called first
		if (!$this->user->loggedIn) {
			// do something
		}
	}
}
