<?php declare(strict_types = 1);

namespace PHPStan\Node;

use Override;
use PhpParser\Node;
use PhpParser\NodeAbstract;
use PHPStan\Turbo\ReferencedByTurboExtension;
use PHPStan\Type\Type;

/**
 * What the function body assigns to a variable a `@var` declaration over a
 * value that does not decide its type (`null`, `[]`, a scalar, a `new` of a
 * generic class, a `static` variable) declares - see VarTagUsagesInference.
 * Either a write to the variable after the declaration (an assignment, a
 * compound assignment, an increment or decrement, an offset write,
 * destructuring), or the declaration of a generic `new` with the template
 * arguments the body infers when the tag is left out.
 */
#[ReferencedByTurboExtension(key: 'varTagUsagesNode')]
final class VarTagUsagesNode extends NodeAbstract implements VirtualNode
{

	public function __construct(
		Node $node,
		private string $variableName,
		private Type $varTagType,
		private Type $assignedType,
		private Type $preciseAssignedType,
	)
	{
		parent::__construct($node->getAttributes());
	}

	public function getVariableName(): string
	{
		return $this->variableName;
	}

	public function getVarTagType(): Type
	{
		return $this->varTagType;
	}

	/**
	 * The variable's type after the write when it held the type the tag
	 * declares before it, or the declared generic `new` with
	 * the template arguments the body infers generalized like inferred template
	 * arguments are.
	 */
	public function getAssignedType(): Type
	{
		return $this->assignedType;
	}

	/** getAssignedType() with the inferred template arguments as precise as the body makes them. */
	public function getPreciseAssignedType(): Type
	{
		return $this->preciseAssignedType;
	}

	#[Override]
	public function getType(): string
	{
		return 'PHPStan_Node_VarTagUsages';
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
