<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Stmt\ClassMethod;

interface MethodReflectionWithNode extends MethodReflection
{

	public function getNode(): ?ClassMethod;

}
