<?php declare(strict_types = 1);

namespace InvalidInheritDocTag;

class ParentClass
{

	public function methodWithoutPhpDoc(): int
	{
		return 0;
	}

	/**
	 * Some description.
	 *
	 * @return int
	 */
	public function methodWithPhpDoc(): int
	{
		return 0;
	}

}

class ChildWithInlineInheritDoc extends ParentClass
{

	/**
	 * {@inheritdoc}
	 */
	public function methodWithoutPhpDoc(): int
	{
		return parent::methodWithoutPhpDoc();
	}

	/**
	 * {@inheritdoc}
	 */
	public function methodWithPhpDoc(): int
	{
		return parent::methodWithPhpDoc();
	}

}

class ChildWithBlockInheritDoc extends ParentClass
{

	/**
	 * @inheritdoc
	 */
	public function methodWithoutPhpDoc(): int
	{
		return parent::methodWithoutPhpDoc();
	}

	/**
	 * @inheritDoc
	 */
	public function methodWithPhpDoc(): int
	{
		return parent::methodWithPhpDoc();
	}

}

class ClassWithoutParent
{

	/**
	 * {@inheritdoc}
	 */
	public function orphanedInheritDoc(): int
	{
		return 0;
	}

	/**
	 * @inheritdoc
	 */
	public function orphanedBlockInheritDoc(): int
	{
		return 0;
	}

}

interface ParentInterface
{

	public function interfaceMethodWithoutPhpDoc(): int;

	/**
	 * Description.
	 */
	public function interfaceMethodWithPhpDoc(): int;

}

class ImplementsInterface implements ParentInterface
{

	/**
	 * {@inheritdoc}
	 */
	public function interfaceMethodWithoutPhpDoc(): int
	{
		return 0;
	}

	/**
	 * {@inheritdoc}
	 */
	public function interfaceMethodWithPhpDoc(): int
	{
		return 0;
	}

}

class NoInheritDocTag
{

	/**
	 * Just description.
	 *
	 * @return int
	 */
	public function plainMethod(): int
	{
		return 0;
	}

}

class TypoInheritDoc
{

	/**
	 * @inheritdocs
	 */
	public function withTypo(): int
	{
		return 0;
	}

}

class GrandparentWithPhpDoc
{

	/**
	 * Grandparent description.
	 */
	public function inheritedMethod(): int
	{
		return 0;
	}

}

class ParentInheritsFromGrandparent extends GrandparentWithPhpDoc
{

	public function inheritedMethod(): int
	{
		return parent::inheritedMethod();
	}

}

class ChildInheritsTransitively extends ParentInheritsFromGrandparent
{

	/**
	 * {@inheritdoc}
	 */
	public function inheritedMethod(): int
	{
		return parent::inheritedMethod();
	}

}

trait TraitWithoutPhpDoc
{

	public function traitMethodWithoutPhpDoc(): int
	{
		return 0;
	}

}

trait TraitWithPhpDoc
{

	/**
	 * Trait description.
	 */
	public function traitMethodWithPhpDoc(): int
	{
		return 0;
	}

}

class UsesTraitWithoutPhpDoc
{

	use TraitWithoutPhpDoc;

	/**
	 * {@inheritdoc}
	 */
	public function traitMethodWithoutPhpDoc(): int
	{
		return 0;
	}

}

class UsesTraitWithPhpDoc
{

	use TraitWithPhpDoc;

	/**
	 * {@inheritdoc}
	 */
	public function traitMethodWithPhpDoc(): int
	{
		return 0;
	}

}

class IssueExampleParent
{

	public function f(): int
	{
		return 0;
	}

}

class IssueExampleChild extends IssueExampleParent
{

	/**
	 * {@inheritdoc}
	 */
	public function f(): int
	{
		return parent::f();
	}

}

class PrivateParentMethod
{

	/**
	 * Private description.
	 */
	private function privateMethod(): int
	{
		return 0;
	}

}

class ChildOfPrivateParentMethod extends PrivateParentMethod
{

	/**
	 * {@inheritdoc}
	 */
	public function privateMethod(): int
	{
		return 0;
	}

}

trait OrphanedInheritDocTrait
{

	/**
	 * {@inheritdoc}
	 */
	public function orphaned(): int
	{
		return 0;
	}

}

class UsesOrphanedTrait
{

	use OrphanedInheritDocTrait;

}

interface BaseInterface
{

	/**
	 * Base description.
	 */
	public function baseMethod(): int;

}

interface ExtendingInterface extends BaseInterface
{

	/**
	 * {@inheritdoc}
	 */
	public function baseMethod(): int;

}

class InheritDocMentionedInDescription
{

	/**
	 * Please do not add `{@inheritdoc}` to this method.
	 */
	public function methodWithInheritDocInBackticks(): int
	{
		return 0;
	}

	/**
	 * Foo @inheritDoc
	 */
	public function methodWithInheritDocInTextNotAtLineStart(): int
	{
		return 0;
	}

}
