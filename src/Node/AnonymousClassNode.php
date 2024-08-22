<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Stmt\Class_;

/**
 * The only purpose of this is to fix {@see Class_::isAnonymous()}, broken by us giving anonymous classes a name.
 */
final class AnonymousClassNode extends Class_
{

	public function isAnonymous(): bool
	{
		return true;
	}

}
