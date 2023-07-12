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
