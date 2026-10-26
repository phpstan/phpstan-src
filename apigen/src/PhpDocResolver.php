<?php declare(strict_types = 1);

namespace PHPStan\ApiGen;

use ApiGen\Analyzer\NodeVisitors\PhpDocResolver as BasePhpDocResolver;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocNode;
use PHPStan\PhpDocParser\Ast\PhpDoc\PhpDocTextNode;
use function array_merge;

/**
 * phpdoc-parser puts each paragraph into its own text node with an empty text node
 * between them. ApiGen drops the empty nodes, which merges all paragraphs into one,
 * so they are kept as blank lines here.
 */
final class PhpDocResolver extends BasePhpDocResolver
{

	public const PARAGRAPH_SEPARATOR = "\n\n";

	protected function resolvePhpDoc(PhpDocNode $phpDoc): PhpDocNode
	{
		$children = [];
		foreach ($phpDoc->children as $child) {
			if ($child instanceof PhpDocTextNode && $child->text === '') {
				$children[] = new PhpDocTextNode(self::PARAGRAPH_SEPARATOR);
				continue;
			}

			$children = array_merge($children, parent::resolvePhpDoc(new PhpDocNode([$child]))->children);
		}

		return new PhpDocNode($children);
	}

}
