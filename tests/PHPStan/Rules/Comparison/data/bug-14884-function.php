<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug14884Function;

class Obj
{

	public function __construct(
		public ?int $remoteId,
		public bool $mirrored,
	)
	{
	}

	public function getRemoteId(): ?int
	{
		return $this->remoteId;
	}

}

/**
 * @phpstan-assert-if-true !null $o->getRemoteId()
 */
function isMirrored(Obj $o): bool
{
	return $o->mirrored && hasRemote($o);
}

/**
 * @phpstan-assert-if-true !null $o->getRemoteId()
 */
function hasRemote(Obj $o): bool
{
	return $o->remoteId !== null;
}

function test(Obj $o): void
{
	$link = hasRemote($o) ? 'link' : null;
	$canMirror = !isMirrored($o) && !hasRemote($o);
}
