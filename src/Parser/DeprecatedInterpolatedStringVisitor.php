<?php declare(strict_types = 1);

namespace PHPStan\Parser;

use Override;
use PhpParser\Node;
use PhpParser\Node\Expr\ArrayDimFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Scalar\InterpolatedString;
use PhpParser\NodeVisitorAbstract;
use PhpParser\Token;
use PHPStan\DependencyInjection\AutowiredService;

#[AutowiredService]
final class DeprecatedInterpolatedStringVisitor extends NodeVisitorAbstract implements TokenAwareVisitor
{
	public const ATTRIBUTE_NAME = 'isDeprecatedInterpolatedString';

	/**
	 * @var Token[]
	 */
	protected array $tokens = [];

	#[Override]
	public function setTokens(array $tokens): void
	{
		$this->tokens = $tokens;
	}

	#[Override]
	public function enterNode(Node $node): ?Node
	{
		if (!$node instanceof InterpolatedString) {
			return null;
		}

		foreach ($node->parts as $part) {
			if (!$part instanceof Variable && !($part instanceof ArrayDimFetch && $part->var instanceof Variable)) {
				continue;
			}
			$startTokenPos = $part->getStartTokenPos();
			if (!isset($this->tokens[$startTokenPos])) {
				continue;
			}
			$startToken = (string) $this->tokens[$startTokenPos];
			if ($startToken !== '${') {
				continue;
			}

			$node->setAttribute(self::ATTRIBUTE_NAME, true);
		}

		return null;
	}

}
