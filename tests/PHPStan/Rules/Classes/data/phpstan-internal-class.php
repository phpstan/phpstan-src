<?php // lint >= 7.4

namespace _PHPStan_156ee64ba; // mimicks a prefixed class, as contained in phpstan.phar releases

class PrefixedRuntimeException extends \RuntimeException {}

class AClass {
	const Test = 1;
}


namespace TestPhpstanInternalClass;

use _PHPStan_156ee64ba\PrefixedRuntimeException;

function doFoo(\_PHPStan_156ee64ba\AClass $e) {
	try {

	} catch (\_PHPStan_156ee64ba\PrefixedRuntimeException $exception) {

	}
}

class Foo {
   private \_PHPStan_156ee64ba\AClass $e;

   public function doFoo() {
	   echo \_PHPStan_156ee64ba\AClass::Test;

	   new \_PHPStan_156ee64ba\AClass();
   }
}

class Bar extends \_PHPStan_156ee64ba\AClass
{}

