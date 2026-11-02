<?php declare(strict_types = 1);

namespace PHPStan\Command\Neon2Attributes;

use Override;
use PhpParser\Node;
use PhpParser\PrettyPrinter\Standard;

/**
 * Prints attributes of parameters in a one-line parameter list on the same line,
 * other attributes go on their own line.
 */
final class AttributesPrinter extends Standard
{

	public const INLINE_ATTRIBUTES = 'neon2attributesInline';

	#[Override]
	protected function pParam(Node\Param $node): string
	{
		if ($node->getAttribute(self::INLINE_ATTRIBUTES) !== true) {
			return parent::pParam($node);
		}

		$param = clone $node;
		$param->attrGroups = [];

		return $this->pAttrGroups($node->attrGroups, true) . parent::pParam($param);
	}

}
