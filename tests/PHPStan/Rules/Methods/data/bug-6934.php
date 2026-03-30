<?php // lint >= 8.0

declare(strict_types = 1);

namespace Bug6934Rule;

use DOMNode;

function removeFromParent(?DOMNode $node): void {
	$node?->parentNode?->removeChild($node);
}
