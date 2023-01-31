<?php

namespace ApiInstanceofType;

use PHPStan\Type\Type;
use PHPStan\Type\TypeTraverser;
use PHPStan\Type\TypeWithClassName;
use stdClass;

class Foo
{

	public function doFoo($a, Type $type)
	{
		if ($a instanceof stdClass) {

		}

		if ($a instanceof TypeWithClassName) {

		}

		if ($a instanceof \phpstan\type\typewithclassname) {

		}

		$type = TypeTraverser::map($type, function (Type $type, callable $traverse): Type {
			if ($type instanceof TypeWithClassName) {
				return $type;
			}

			return $traverse($type);
		});

		if ($a instanceof TypeWithClassName) {

		}
	}

}
