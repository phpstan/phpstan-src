<?php declare(strict_types = 1);

namespace PHPStan\Node;

use PhpParser\Node\Expr;
use PhpParser\Node\Identifier;
use PhpParser\Node\Name;

class StaticMethodCallableNode extends Expr implements VirtualNode
{

	/** @var Name|Expr */
	private $class;

	/** @var Identifier|Expr */
	public $name;

	/**
	 * @param Name|Expr $class
	 * @param Identifier|Expr $name
	 * @param mixed[] $attributes
	 */
	public function __construct($class, $name, array $attributes = [])
	{
		parent::__construct($attributes);
		$this->class = $class;
		$this->name = $name;
	}

	/**
	 * @return Expr|Name
	 */
	public function getClass()
	{
		return $this->class;
	}

	/**
	 * @return Identifier|Expr
	 */
	public function getName()
	{
		return $this->name;
	}

	public function getType(): string
	{
		return 'PHPStan_Node_StaticMethodCallableNode';
	}

	/**
	 * @return string[]
	 */
	public function getSubNodeNames(): array
	{
		return [];
	}

}
