<?php declare(strict_types = 1);

namespace FriendPhpDocs;

use LogicException;
use RuntimeException;

interface Foo
{
	public function noFriends();

	/**
	 * @friend Bar
	 */
	public function classAsFriend();

	/**
	 * @friend Bar::baz
	 */
	public function methodAsFriend();

	/**
	 * @friend Bar
	 * @friend Bar::baz
	 */
	public function multipleFriends();

	/**
	 * @friend
	 * @friend bool
	 */
	public function invalidFriends();
}

interface Bar
{
	public function baz();
}
