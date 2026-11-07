<?php declare(strict_types = 1);

namespace CircularInheritance;

class ExtendsSelf extends ExtendsSelf
{

}

class FirstOfPair extends SecondOfPair
{

}

class SecondOfPair extends FirstOfPair
{

}

class FirstOfThree extends SecondOfThree
{

}

class SecondOfThree extends ThirdOfThree
{

}

class ThirdOfThree extends FirstOfThree
{

}

class ExtendsClassOnCycle extends FirstOfPair
{

}

interface InterfaceExtendsSelf extends InterfaceExtendsSelf
{

}

interface FirstInterfaceOfPair extends SecondInterfaceOfPair
{

}

interface SecondInterfaceOfPair extends FirstInterfaceOfPair
{

}

interface Top
{

}

interface Left extends Top
{

}

interface Right extends Top
{

}

interface Bottom extends Left, Right
{

}

interface ExtendsInterfaceOnCycle extends FirstInterfaceOfPair, Top
{

}

abstract class ImplementsInterfaceOnCycle implements FirstInterfaceOfPair
{

}

trait TraitUsesSelf
{

	use TraitUsesSelf;

}

trait FirstTraitOfPair
{

	use SecondTraitOfPair;

}

trait SecondTraitOfPair
{

	use FirstTraitOfPair;

}

trait DiamondBottom
{

}

trait DiamondLeft
{

	use DiamondBottom;

}

trait DiamondRight
{

	use DiamondBottom;

}

class UsesDiamond
{

	use DiamondLeft, DiamondRight;

}

class UsesTraitOnCycle
{

	use FirstTraitOfPair;

}

class ExtendsSelfWithDifferentCase extends extendsselfwithdifferentcase
{

}
