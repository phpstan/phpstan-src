<?php

namespace InvalidFriends;

class Foo
{
	/**
	 * @friend Bar
	 * @friend Foo::notExists
	 */
	public function bar() {  }
}
