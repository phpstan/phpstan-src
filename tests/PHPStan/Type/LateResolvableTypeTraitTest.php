<?php declare(strict_types = 1);

namespace PHPStan\Type;

use PHPStan\Type\Constant\ConstantStringType;
use PHPStan\Type\IntegerType;
use PHPStan\Type\NeverType;
use PHPStan\Type\UnionType;
use PHPUnit\Framework\TestCase;

class LateResolvableTypeTraitTest extends TestCase
{

	public function testIsSuperTypeOfForConditional(): void
	{
		$conditional = new ConditionalTypeForParameter(
			'$operator',
			new ConstantStringType('in'),
			new IntegerType(),
			new NeverType(),
			false,
		);

		$this->assertSame('Yes', $conditional->isSuperTypeOf($conditional)->describe());

		$unionWithConditional = new UnionType([
			new StringType(),
			$conditional,
		]);

		$this->assertSame('Yes', $conditional->isSuperTypeOf($unionWithConditional)->describe());
	}

}
