<?php // lint >= 8.6

namespace ConstantOverrideAttr;

interface I
{
	public const I = 'I';
}

interface II extends I
{
	#[\Override]
	public const I = 'I';
}

interface Unrelated
{
	#[\Override]
	public const U = 'U';
}

class P
{
	public const C1 = 'C1';
	public const C2 = 'C2';
	public const C3 = 'C3';
	public const C4 = 'C4';
	protected const PROTECTED = 'P';
	private const PRIVATE = 'P';
}

class PP extends P
{
	#[\Override]
	public const C1 = 'C1';
	public const C2 = 'C2';
	#[\Override]
	public const C3 = 'C3';
}

class C extends PP implements I
{
	#[\Override]
	public const I = 'I';
	#[\Override]
	public const C1 = 'C1';
	#[\Override]
	public const C2 = 'C2';
	public const C3 = 'C3';
	#[\Override]
	public const C4 = 'C4';
	public const C = 'C';
	#[\Override]
	public const PROTECTED = 'P';
	#[\Override]
	public const PRIVATE = 'P';
	#[\Override]
	public const NOTHING = 'N';
	#[\Override]
	public const C5 = 'C5', C6 = 'C6';
}

enum E implements I
{
	#[\Override]
	public const I = 'I';
	#[\Override]
	public const C = 'C';
}

trait DemoTrait
{
	#[\Override]
	public const C1 = 'C1';
}

class UsesTraitWithParent extends P
{
	use DemoTrait;
}

class UsesTraitWithoutParent
{
	use DemoTrait;
}

trait TraitWithoutAttribute
{
	public const C2 = 'C2';
}

class UsesTraitWithoutAttribute extends P
{
	use TraitWithoutAttribute;
}

trait RedeclaredTrait
{
	public const T = 'T';
}

class RedeclaresTraitConstant
{
	use RedeclaredTrait;

	#[\Override]
	public const T = 'T';
}

function (): void {
	new class () implements I {
		#[\Override]
		public const I = 'I';
	};
	new class () {
		#[\Override]
		public const I = 'I';
	};
};
