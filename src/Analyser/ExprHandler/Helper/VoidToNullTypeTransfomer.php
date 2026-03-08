<?php declare(strict_types = 1);

namespace PHPStan\Analyser\ExprHandler\Helper;

use PhpParser\Node;
use PHPStan\Analyser\MutatingScope;
use PHPStan\Analyser\Traverser\VoidToNullTraverser;
use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;

final class VoidToNullTypeTransfomer
{

	public static function transform(Type $type, Node $node): Type
	{
		if ($node->getAttribute(MutatingScope::KEEP_VOID_ATTRIBUTE_NAME) === true) {
			return $type;
		}

		return TypeTraverser::map($type, new VoidToNullTraverser());
	}

}
