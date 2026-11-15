<?php declare(strict_types = 1);

/*
 * instructions: 333.8M
 * cycles: 456.1M
 * memory: 5.161MiB
 */

namespace BenchNullsafeChainNarrowing;

final class Link0
{

	public ?Link1 $next = null;

	public function next(): ?Link1
	{
		return $this->next;
	}

}

final class Link1
{

	public ?Link2 $next = null;

	public function next(): ?Link2
	{
		return $this->next;
	}

}

final class Link2
{

	public ?Link3 $next = null;

	public function next(): ?Link3
	{
		return $this->next;
	}

}

final class Link3
{

	public ?Link4 $next = null;

	public function next(): ?Link4
	{
		return $this->next;
	}

}

final class Link4
{

	public ?Link5 $next = null;

	public function next(): ?Link5
	{
		return $this->next;
	}

}

final class Link5
{

	public ?Link6 $next = null;

	public function next(): ?Link6
	{
		return $this->next;
	}

}

final class Link6
{

	public ?Link7 $next = null;

	public function next(): ?Link7
	{
		return $this->next;
	}

}

final class Link7
{

	public ?Link8 $next = null;

	public function next(): ?Link8
	{
		return $this->next;
	}

}

final class Link8
{

	public ?Link9 $next = null;

	public function next(): ?Link9
	{
		return $this->next;
	}

}

final class Link9
{

	public ?Link10 $next = null;

	public function next(): ?Link10
	{
		return $this->next;
	}

}

final class Link10
{

	public ?Link11 $next = null;

	public function next(): ?Link11
	{
		return $this->next;
	}

}

final class Link11
{

	public ?Link12 $next = null;

	public function next(): ?Link12
	{
		return $this->next;
	}

}

final class Link12
{

	public ?Link13 $next = null;

	public function next(): ?Link13
	{
		return $this->next;
	}

}

final class Link13
{

	public ?Link14 $next = null;

	public function next(): ?Link14
	{
		return $this->next;
	}

}

final class Link14
{

	public ?Link15 $next = null;

	public function next(): ?Link15
	{
		return $this->next;
	}

}

final class Link15
{

	public ?Link16 $next = null;

	public function next(): ?Link16
	{
		return $this->next;
	}

}

final class Link16
{

	public int $value = 1;

}

/**
 * A chain of 16 `?->` links, as property fetches and as method calls. Each
 * link's variable flow is the choice between the fetch, which evaluates the
 * receiver first, and the short circuit, which evaluates only the receiver,
 * so the receiver's flow was in both branches and VariableLivenessResolver
 * walked the first link 2^N times. Narrowing the chain not null asked each
 * link for "the receiver is not null" twice, through the plain fetch's
 * receiver and through its own receiver term, which was exponential too.
 */
function run(Link0 $link): int
{
	echo $link?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->value;
	if ($link?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next !== null) {
		echo $link?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next?->next->value;
	}
	echo $link?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->value;
	if ($link?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next() !== null) {
		return $link?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()?->next()->value;
	}

	return 0;
}
