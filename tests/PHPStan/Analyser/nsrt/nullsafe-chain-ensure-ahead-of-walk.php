<?php // lint >= 8.0

declare(strict_types = 1);

namespace NullsafeChainEnsureAheadOfWalk;

use function PHPStan\Testing\assertType;

class Leaf { public ?string $aaa = null; }
class Mid { public ?Leaf $prop = null; public function get(): ?Leaf { return null; } }
class Root { public function get(): ?Mid { return null; } }

function doFoo(Root $root): void
{
	// isset()/empty()/?? ensure every link of the chain non-null ahead of the
	// walk - a ?-> link must be answered from the scope's state, not walked
	assertType('mixed~null', $root->get()?->prop?->get()?->aaa ?? 'edge');
	assertType('bool', isset($root->get()?->prop?->get()?->aaa));
	assertType('bool', empty($root->get()?->prop?->get()?->aaa));
}
