<?php

namespace Bug2740;

/**
 * A collection that can contain members.
 *
 * @extends \IteratorAggregate<int,Member>
 */
interface Collection extends \IteratorAggregate
{
}

/**
 * A member of a collection. Also a collection containing only itself.
 *
 * In the real world, this would contain additional methods.
 */
interface Member extends Collection
{

}

class MemberImpl implements Member
{

	/**
	 * @return \Iterator<int,Member>
	 */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator([$this]);
	}

}

class CollectionImpl implements Collection
{

	/**
	 * @var array<int,Member>
	 */
	private $members;

	public function __construct(Member ...$members)
	{
		$this->members = $members;
	}

	/**
	 * @return Member
	 */
	public function getMember(): Member
	{
		return new MemberImpl();
	}

	/**
	 * @return \Iterator<int,Member>
	 */
	public function getIterator(): \Iterator
	{
		return new \ArrayIterator($this->members);
	}

}
