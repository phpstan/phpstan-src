<?php // lint >= 8.1

namespace Bug10523;

final class Controller
{
	private readonly B $userAccount;

	public function __construct()
	{
		$this->userAccount = new B();
	}

	public function init(): void
	{
		$this->redirectIfNkdeCheckoutNotAllowed();
		$this->redirectIfNoShoppingBasketPresent();
	}

	private function redirectIfNkdeCheckoutNotAllowed(): void
	{

	}

	private function redirectIfNoShoppingBasketPresent(): void
	{
		$x = $this->userAccount;
	}

}

class B {}

final class MultipleWrites
{
	private readonly B $userAccount;

	public function __construct()
	{
		$this->userAccount = new B();
	}

	public function init(): void
	{
		$this->redirectIfNkdeCheckoutNotAllowed();
		$this->redirectIfNoShoppingBasketPresent();
	}

	private function redirectIfNkdeCheckoutNotAllowed(): void
	{
	}

	private function redirectIfNoShoppingBasketPresent(): void
	{
		$this->userAccount = new B();
	}

}
