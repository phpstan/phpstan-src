<?php declare(strict_types=1); // lint <= 7.4

namespace OffsetAccessLegalNonExistentParent;

class Foo
{
	public function doBar(): void
	{
		(new parent())[0] ?? 'error';
	}
}
