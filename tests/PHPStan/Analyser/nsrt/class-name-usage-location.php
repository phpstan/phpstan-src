<?php

namespace ClassNameUsageLocation;

use PHPStan\Rules\ClassNameUsageLocation;
use function PHPStan\Testing\assertType;

function (ClassNameUsageLocation $location): void {
	assertType("'assert.test'|'attribute.test'|'catch.test'|'class.extendsTest'|'class.implementsTest'|'classConstant.test'|'enum.implementsTest'|'generics.testBound'|'generics.testDefault'|'instanceof.test'|'interface.extendsTest'|'methodTag.test'|'mixin.test'|'new.test'|'parameter.test'|'property.test'|'propertyTag.test'|'requireExtends.test'|'requireImplements.test'|'return.test'|'sealed.test'|'selfOut.test'|'staticMethod.test'|'staticProperty.test'|'traitUse.test'|'typeAlias.test'|'varTag.test'", $location->createIdentifier('test'));

	if ($location->value === ClassNameUsageLocation::INSTANTIATION || $location->value === ClassNameUsageLocation::PROPERTY_TYPE) {
		assertType("'new.test'|'property.test'", $location->createIdentifier('test'));
	}
};
