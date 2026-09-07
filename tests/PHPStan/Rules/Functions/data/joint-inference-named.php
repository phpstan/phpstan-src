<?php // lint >= 8.0

declare(strict_types = 1);

namespace JointInference;

use function PHPStan\Testing\assertType;

require_once __DIR__ . '/../../../Analyser/Generics/data/joint-inference.php';

function namedArguments(): void
{
	$a = new Box(1);
	$b = new Box(2);
	both(b: $b, a: $a);
	assertType('JointInference\\Box<1|2>', $a);
	assertType('JointInference\\Box<1|2>', $b);
}
