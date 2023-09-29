<?php

namespace ImpureConnectionFunctions;

use function PHPStan\Testing\assertType;

function doFoo() {
   if (connection_aborted()) {
	   assertType('0|1', connection_aborted());
   }

	if (connection_status()) {
		assertType('int<0, 3>', connection_status());
	}
}
