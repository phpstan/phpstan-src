<?php

namespace FriendMethodCallTest;

$foo = new Foo();
$foo->friendWithEntireBar();

class Foo
{
	public function casual() {  }

	/** @friend Bar */
	public function friendWithEntireBar() {  }

	/** @friend Bar::baz */
	public function friendWithBarBaz() {  }
}

class Bar
{
	public function baz()
	{
		$foo = new Foo();
		$foo->casual();
		$foo->friendWithEntireBar();
		$foo->friendWithBarBaz();
	}

	public function notBaz()
	{
		$foo = new Foo();
		$foo->casual();
		$foo->friendWithEntireBar();
		$foo->friendWithBarBaz();
	}
}

class BarBar
{
	public function baz()
	{
		$foo = new Foo();
		$foo->casual();
		$foo->friendWithEntireBar();
		$foo->friendWithBarBaz();
	}

	public function notBaz()
	{
		$foo = new Foo();
		$foo->casual();
		$foo->friendWithEntireBar();
		$foo->friendWithBarBaz();
	}
}

class Qux
{
	public function smh()
	{
		$foo = new Foo();
		$foo->casual();
		$foo->friendWithEntireBar();
		$foo->friendWithBarBaz();
	}
}

class SelfFriend
{
	public function caller()
	{
		$this->notFriend();
		$this->friendWithSelfFriend();
		$this->friendWithSelf();
		$this->friendWithStatic();
	}

	/** @friend Foo */
	public function notFriend() {  }

	/** @friend SelfFriend */
	public function friendWithSelfFriend() {  }

	/** @friend self */
	public function friendWithSelf() {  }

	/** @friend static */
	public function friendWithStatic() {  }
}
