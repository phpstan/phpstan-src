<?php // lint >= 8.4

namespace PrivateFinalHook;

final class User
{
	final private string $privatePropGet = 'mailto: example.org' {
		get => 'private:' . $this->privatePropGet;
	}

	private string $private = 'mailto: example.org' {
		final set => 'private:' . $this->private;
		get => 'private:' . $this->private;
	}

	protected string $protected = 'mailto: example.org' {
		final get => 'protected:' . $this->protected;
	}

	public string $public = 'mailto: example.org' {
		final get => 'public:' . $this->public;
	}

	private string $email = 'mailto: example.org' {
		get => 'mailto:' . $this->email;
	}

	function doFoo(): void
	{
		$u = new User;
		var_dump($u->private);
		var_dump($u->protected);
		var_dump($u->public);
		var_dump($u->email);
	}
}
