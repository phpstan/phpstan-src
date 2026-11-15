<?php // lint >= 8.1

namespace OverridingConstantMultiplePrototypes;

interface NotFinal
{

	const C = 1;

}

interface IsFinal
{

	final const C = 2;

}

class ImplementsBoth implements NotFinal, IsFinal
{

	const C = 3;

}

class ParentClass
{

	const C = 1;

	protected const D = 1;

}

class ExtendsAndImplementsFinal extends ParentClass implements IsFinal
{

	const C = 2;

}

interface PublicD
{

	const D = 1;

}

class ExtendsAndImplementsPublic extends ParentClass implements PublicD
{

	protected const D = 2;

}

class ParentImplementingNotFinal implements NotFinal
{

}

class ChildOfParentImplementingNotFinal extends ParentImplementingNotFinal
{

	const C = 4;

}

class ParentWithFinal
{

	final const E = 1;

}

interface HasE
{

	const E = 1;

}

class ExtendsFinalAndImplements extends ParentWithFinal implements HasE
{

	const E = 2;

}
