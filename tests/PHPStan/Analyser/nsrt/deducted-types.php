<?php

namespace TypesNamespaceDeductedTypes;

use function PHPStan\Testing\assertType;

use TypesNamespaceFunctions;

final class Foo
{

	const INTEGER_CONSTANT = 1;
	const FLOAT_CONSTANT = 1.0;
	const STRING_CONSTANT = 'foo';
	const ARRAY_CONSTANT = [];
	const BOOLEAN_CONSTANT = true;
	const NULL_CONSTANT = null;

	public function doFoo()
	{
		$integerLiteral = 1;
		$booleanLiteral = true;
		$anotherBooleanLiteral = false;
		$stringLiteral = 'foo';
		$floatLiteral = 1.0;
		$floatAssignedByRef = &$floatLiteral;
		$nullLiteral = null;
		$loremObjectLiteral = new Lorem();
		$mixedObjectLiteral = new $class();
		$newStatic = new static();
		$arrayLiteral = [];
		$stringFromFunction = TypesNamespaceFunctions\stringFunction();
		$fooObjectFromFunction = TypesNamespaceFunctions\objectFunction();
		$mixedFromFunction = TypesNamespaceFunctions\unknownTypeFunction();
		$foo = new self();
		assertType('1', $integerLiteral);
		assertType('true', $booleanLiteral);
		assertType('false', $anotherBooleanLiteral);
		assertType('\'foo\'', $stringLiteral);
		assertType('1.0', $floatLiteral);
		assertType('1.0', $floatAssignedByRef);
		assertType('null', $nullLiteral);
		assertType('TypesNamespaceDeductedTypes\Lorem', $loremObjectLiteral);
		assertType('object', $mixedObjectLiteral);
		assertType('static(TypesNamespaceDeductedTypes\Foo)', $newStatic);
		assertType('array{}', $arrayLiteral);
		assertType('string', $stringFromFunction);
		assertType('TypesNamespaceFunctions\Foo', $fooObjectFromFunction);
		assertType('mixed', $mixedFromFunction);
		assertType('1', \TypesNamespaceDeductedTypes\Foo::INTEGER_CONSTANT);
		assertType('1', self::INTEGER_CONSTANT);
		assertType('1.0', self::FLOAT_CONSTANT);
		assertType('\'foo\'', self::STRING_CONSTANT);
		assertType('array{}', self::ARRAY_CONSTANT);
		assertType('true', self::BOOLEAN_CONSTANT);
		assertType('null', self::NULL_CONSTANT);
		assertType('1', $foo::INTEGER_CONSTANT);
		assertType('1.0', $foo::FLOAT_CONSTANT);
		assertType('\'foo\'', $foo::STRING_CONSTANT);
		assertType('array{}', $foo::ARRAY_CONSTANT);
		assertType('true', $foo::BOOLEAN_CONSTANT);
		assertType('null', $foo::NULL_CONSTANT);
	}

}
