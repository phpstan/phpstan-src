<?php declare(strict_types = 1);

namespace PHPStan\ApiGen;

use League\CommonMark\Extension\CommonMark\Node\Inline\HtmlInline;
use League\CommonMark\Node\Node;
use League\CommonMark\Renderer\ChildNodeRendererInterface;
use League\CommonMark\Renderer\NodeRendererInterface;
use League\CommonMark\Util\Xml;
use function assert;
use function in_array;
use function preg_match;

/**
 * Keeps the HTML formatting tags PHPDocs use, and escapes everything else that
 * CommonMark parses as an inline HTML tag, such as the `<T>` in `array<T>`.
 */
final class HtmlInlineRenderer implements NodeRendererInterface
{

	private const ALLOWED_TAGS = ['a', 'b', 'br', 'code', 'em', 'i', 'kbd', 'li', 'ol', 'p', 'pre', 'strong', 'sub', 'sup', 'ul'];

	public function render(Node $node, ChildNodeRendererInterface $childRenderer): string
	{
		assert($node instanceof HtmlInline);

		$literal = $node->getLiteral();
		if (preg_match('~^</?([a-z]+)[\s/>]~', $literal, $matches) === 1 && in_array($matches[1], self::ALLOWED_TAGS, true)) {
			return $literal;
		}

		return Xml::escape($literal);
	}

}
