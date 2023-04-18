<?php declare(strict_types = 1);

namespace PHPStan\Rules\Properties;

use PHPStan\Reflection\AdditionalConstructorsExtension;
use PHPStan\Reflection\ClassReflection;

class TestInitializedProperty implements AdditionalConstructorsExtension
{

	public function getAdditionalConstructors(ClassReflection $classReflection): array
	{
		if ($classReflection->getName() === 'TestInitializedProperty\\TestAdditionalConstructor') {
			return ['setTwo'];
		}

		return [];
	}

}
