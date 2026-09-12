<?php declare(strict_types = 1);

namespace PHPStan\Node\Expr;

use Override;
use PhpParser\Node\Expr;
use PHPStan\Node\VirtualNode;
use function sprintf;

/**
 * Links the foreach value variable to the iteratee dim fetch it currently
 * aliases ($array[$key]), so a narrowing landed on the value variable can be
 * projected onto the tracked dim fetch. Both the value variable and the dim
 * fetch are sub-nodes on purpose: a write to the value variable, the key
 * variable or the iteratee must invalidate the link through containment.
 */
final class ForeachValueAliasExpr extends Expr implements VirtualNode
{

	public const KEY_PREFIX = '__phpstanForeachValueAlias(';

	public Expr\Variable $var;

	public function __construct(private string $variableName, public Expr\ArrayDimFetch $dimFetch)
	{
		parent::__construct([]);
		$this->var = new Expr\Variable($this->variableName);
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	public function getDimFetch(): Expr\ArrayDimFetch
	{
		return $this->dimFetch;
	}

	/** The expression key this node prints to - derivable from the value variable name alone. */
	public static function key(string $variableName): string
	{
		return sprintf('%s%s)', self::KEY_PREFIX, $variableName);
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_ForeachValueAliasExpr';
	}

	/**
	 * @return string[]
	 */
	#[Override]
	public function getSubNodeNames(): array
	{
		return ['var', 'dimFetch'];
	}

}
