<?php

namespace PHPStan\ClassConstFetch;

use PHPStan\Command\AnalyseCommand;
use PHPStan\Reflection\ClassReflection;

class Foo
{

	public function doFoo()
	{
		echo ClassReflection::class;
		echo AnalyseCommand::class;
	}

}
