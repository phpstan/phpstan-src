<?php declare(strict_types = 1);

namespace PHPStan\Reflection\Php;

use PHPStan\Reflection\Assertions;
use PHPStan\Reflection\FunctionReflection;
use PHPStan\Reflection\FunctionVariantWithPhpDocs;
use PHPStan\Reflection\ParametersAcceptorWithPhpDocs;
use PHPStan\Reflection\PassedByReference;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Constant\ConstantIntegerType;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\IntegerType;
use PHPStan\Type\MixedType;
use PHPStan\Type\NeverType;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;

final class ExitFunctionReflection implements FunctionReflection
{

	public function __construct(private string $name)
	{
	}

	public function getName(): string
	{
		return $this->name;
	}

	public function getFileName(): ?string
	{
		return null;
	}

	public function getVariants(): array
	{
		$parameterType = new UnionType([
			new StringType(),
			new IntegerType(),
		]);
		return [
			new FunctionVariantWithPhpDocs(
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				[
					new DummyParameterWithPhpDocs(
						'status',
						$parameterType,
						true,
						PassedByReference::createNo(),
						false,
						new ConstantIntegerType(0),
						$parameterType,
						new MixedType(),
						null,
						TrinaryLogic::createNo(),
						null,
					),
				],
				false,
				new NeverType(true),
				new MixedType(),
				new NeverType(true),
				TemplateTypeVarianceMap::createEmpty(),
			),
		];
	}

	public function getOnlyVariant(): ParametersAcceptorWithPhpDocs
	{
		return $this->getVariants()[0];
	}

	/**
	 * @return ParametersAcceptorWithPhpDocs[]
	 */
	public function getNamedArgumentsVariants(): array
	{
		return $this->getVariants();
	}

	public function acceptsNamedArguments(): bool
	{
		return true;
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function getDeprecatedDescription(): ?string
	{
		return null;
	}

	public function isFinal(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getThrowType(): ?Type
	{
		return null;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function isBuiltin(): bool
	{
		return true;
	}

	public function getAsserts(): Assertions
	{
		return Assertions::createEmpty();
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

	public function isPure(): TrinaryLogic
	{
		return TrinaryLogic::createNo();
	}

}
