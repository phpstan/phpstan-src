<?php

namespace App\ClassConstFetch;

use PHPStan\Analyser\NodeScopeResolver;
use PHPStan\Analyser\ScopeFactory;
use PHPStan\Command\AnalyseCommand;
use PHPStan\Reflection\ClassReflection;

class Foo
{

	public function doFoo()
	{
		echo ClassReflection::class;
		echo AnalyseCommand::class;
		echo NodeScopeResolver::class;
		echo ScopeFactory::class;

		echo NodeScopeResolver::FOO;
	}

}
