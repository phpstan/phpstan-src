<?php declare(strict_types = 1);

namespace PHPStan\Rules\PhpDoc;

use PhpParser\Node as PhpParserNode;
use PHPStan\PhpDocParser\Ast\Node as PhpDocNode;

final class PhpDocLineHelper
{

	/**
	 * This method returns exact line of e.g. `@param` tag in PHPDoc so that it can be used for precise error reporting
	 * - exact position is available only when bleedingEdge is enabled
	 * - otherwise, it falls back to given node start line
	 */
	public static function detectLine(PhpParserNode $node, PhpDocNode $phpDocNode): int
	{
		$phpDocTagLine = $phpDocNode->getAttribute('startLine');
		$phpDoc = $node->getDocComment();

		if ($phpDocTagLine === null || $phpDoc === null) {
			return $node->getLine();
		}

		return $phpDoc->getStartLine() + $phpDocTagLine - 1;
	}

}
