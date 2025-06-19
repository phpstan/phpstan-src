<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Stmt\Class_;

/**
 * The only purpose of this is to fix {@see Class_::isAnonymous()}, broken by us giving anonymous classes a name.
 */
final class AnonymousClassNode extends Class_
{

	public static function createFromClassNode(Class_ $node): self
	{
		$subNodes = [];
		foreach ($node->getSubNodeNames() as $subNodeName) {
			$subNodes[$subNodeName] = $node->$subNodeName;
		}

		return new AnonymousClassNode(
			$node->name,
			$subNodes,
			$node->getAttributes(),
		);
	}

	#[Override]
	public function isAnonymous(): bool
	{
		return true;
	}

}
