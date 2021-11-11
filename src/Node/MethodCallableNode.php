<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;

class MethodCallableNode extends Expr implements VirtualNode
{

	private Expr $var;

	/** @var Identifier|Expr */
	public $name;

	/**
	 * @param Expr|Identifier $name
	 * @param mixed[] $attributes
	 */
	public function __construct(Expr $var, $name, array $attributes = [])
	{
		parent::__construct($attributes);
		$this->var = $var;
		$this->name = $name;
	}

	public function getVar(): Expr
	{
		return $this->var;
	}

	/**
	 * @return Expr|Identifier
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_MethodCallableNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
