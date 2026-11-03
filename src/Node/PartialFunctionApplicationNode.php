<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Expr;
use PhpParser\Node\Expr\CallLike;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * Stands in for a call with a `?` or `...` placeholder (PHP 8.6 partial
 * function application) that is not a first-class callable, so that rules
 * for the call itself never see a call whose arguments are placeholders.
 */
#[ReferencedByTurboExtension(key: 'partialFunctionApplicationNode')]
final class PartialFunctionApplicationNode extends Expr implements VirtualNode
{

	public function __construct(private CallLike $originalNode)
	{
		// the original's printed expression key must not become this node's
		$attributes = $originalNode->getAttributes();
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		parent::__construct($attributes);
	}

	public function getOriginalNode(): CallLike
	{
		return $this->originalNode;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_PartialFunctionApplicationNode';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return [];
	}

}
