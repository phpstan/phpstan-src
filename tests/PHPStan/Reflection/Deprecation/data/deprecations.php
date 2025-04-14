<?php // lint >= 8.1

namespace CustomDeprecations;

class NotDeprecatedClass
{
	const FOO = 'foo';

	private $foo;

	public function foo() {}

}


/** @deprecated */
class PhpDocDeprecatedClass
{

	/** @deprecated */
	const FOO = 'foo';

	/** @deprecated */
	private $foo;

	/** @deprecated */
	public function foo() {}

}
/** @deprecated phpdoc */
class PhpDocDeprecatedClassWithMessage
{

	/** @deprecated phpdoc */
	const FOO = 'foo';

	/** @deprecated phpdoc */
	private $foo;

	/** @deprecated phpdoc */
	public function foo() {}

}

#[CustomDeprecated]
class AttributeDeprecatedClass {
	#[CustomDeprecated]
	public const FOO = 'foo';

	#[CustomDeprecated]
	private $foo;

	#[CustomDeprecated]
	public function foo() {}
}

#[CustomDeprecated('attribute')]
class AttributeDeprecatedClassWithMessage {
	#[CustomDeprecated('attribute')]
	const FOO = 'foo';

	#[CustomDeprecated('attribute')]
	private $foo;

	#[CustomDeprecated(description: 'attribute')]
	public function foo() {}
}

/** @deprecated phpdoc */
#[CustomDeprecated('attribute')]
class DoubleDeprecatedClass
{

	/** @deprecated phpdoc */
	#[CustomDeprecated('attribute')]
	const FOO = 'foo';

	/** @deprecated phpdoc */
	#[CustomDeprecated('attribute')]
	private $foo;

	/** @deprecated phpdoc */
	#[CustomDeprecated('attribute')]
	public function foo() {}

}

/** @deprecated */
#[CustomDeprecated('attribute')]
class DoubleDeprecatedClassOnlyAttributeMessage
{

	/** @deprecated */
	#[CustomDeprecated('attribute')]
	const FOO = 'foo';

	/** @deprecated */
	#[CustomDeprecated('attribute')]
	private $foo;

	/** @deprecated */
	#[CustomDeprecated('attribute')]
	public function foo() {}

}

/** @deprecated phpdoc */
#[CustomDeprecated()]
class DoubleDeprecatedClassOnlyPhpDocMessage
{

	/** @deprecated phpdoc */
	#[CustomDeprecated()]
	const FOO = 'foo';

	/** @deprecated phpdoc */
	#[CustomDeprecated()]
	private $foo;

	/** @deprecated phpdoc */
	#[CustomDeprecated()]
	public function foo() {}

}


function notDeprecatedFunction() {}

/** @deprecated */
function phpDocDeprecatedFunction() {}

/** @deprecated phpdoc */
function phpDocDeprecatedFunctionWithMessage() {}

#[CustomDeprecated]
function attributeDeprecatedFunction() {}

#[CustomDeprecated('attribute')]
function attributeDeprecatedFunctionWithMessage() {}

/** @deprecated phpdoc */
#[CustomDeprecated('attribute')]
function doubleDeprecatedFunction() {}

/** @deprecated */
#[CustomDeprecated('attribute')]
function doubleDeprecatedFunctionOnlyAttributeMessage() {}

/** @deprecated phpdoc */
#[CustomDeprecated()]
function doubleDeprecatedFunctionOnlyPhpDocMessage() {}
