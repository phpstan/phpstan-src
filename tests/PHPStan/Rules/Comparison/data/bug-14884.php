<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14884;

class Internal
{

	public function __construct(
		public ?int $remoteId,
		private bool $mirrored,
	)
	{
	}

	/**
	 * @phpstan-assert-if-true !null $this->getRemoteId()
	 */
	public function isMirrored(): bool
	{
		return $this->mirrored && $this->hasRemote();
	}

	/**
	 * @phpstan-assert-if-true !null $this->getRemoteId()
	 */
	public function hasRemote(): bool
	{
		return $this->remoteId !== null;
	}

	public function getRemoteId(): ?int
	{
		return $this->remoteId;
	}

}

function test(Internal $i): void
{
	$link = $i->hasRemote() ? 'link' : null;
	$canMirror = !$i->isMirrored() && !$i->hasRemote();
}
