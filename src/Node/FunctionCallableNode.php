<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node\Arg;
use PhpParser\Node\ArgPlaceholder;
use PhpParser\Node\Expr;
use PhpParser\Node\Name;
use PhpParser\Node\VariadicPlaceholder;
use PHPStan\Node\Printer\ExprPrinter;
use PHPStan\Turbo\ReferencedByTurboExtension;

/**
 * @api
 */
#[ReferencedByTurboExtension(key: 'functionCallableNode')]
final class FunctionCallableNode extends Expr implements VirtualNode
{

	public function __construct(private Name|Expr $name, private Expr\FuncCall $originalNode)
	{
		// the original's printed expression key must not become this node's
		$attributes = $this->originalNode->getAttributes();
		unset($attributes[ExprPrinter::ATTRIBUTE_CACHE_KEY]);
		parent::__construct($attributes);
	}

	/**
	 * @return Expr|Name
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getOriginalNode(): Expr\FuncCall
	{
		return $this->originalNode;
	}

	/**
	 * The arguments of the call: bound arguments, `?` placeholders and the `...` placeholder.
	 *
	 * @return array<Arg|ArgPlaceholder|VariadicPlaceholder>
	 */
	public function getArgs(): array
	{
		return $this->originalNode->getRawArgs();
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_FunctionCallableNode';
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
