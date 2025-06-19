<?php declare(strict_types = 1);

namespace PHPStan\Fixable\PhpDoc;

use Override;
use PHPStan\PhpDocParser\Ast\AbstractNodeVisitor;
use PHPStan\PhpDocParser\Ast\Node;

final class CallbackVisitor extends AbstractNodeVisitor
{

	/** @var callable(Node): (Node|Node[]|null) */
	private $callback;

	/** @param callable(Node): (Node|Node[]|null) $callback */
	public function __construct(callable $callback)
	{
		$this->callback = $callback;
	}

	/**
	 * @return Node[]|Node|null
	 */
	#[Override]
	public function enterNode(Node $node): array|Node|null
	{
		$callback = $this->callback;

		return $callback($node);
	}

}
