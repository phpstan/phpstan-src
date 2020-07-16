<?php declare(strict_types = 1);

namespace MagicSetter;

use PhpParser\Node\Expr\Assign;
use PhpParser\Node\Expr\PropertyFetch;
use PhpParser\Node\Expr\Variable;
use PhpParser\Node\Identifier;
use PhpParser\Node\Param;
use PhpParser\Node\Stmt\Class_;
use PhpParser\Node\Stmt\ClassMethod;
use PhpParser\Node\Stmt\Expression;
use PHPStan\Reflection\ClassMemberReflection;
use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\FunctionVariant;
use PHPStan\Reflection\MethodReflectionWithNode;
use PHPStan\Reflection\Php\DummyParameter;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\StringType;
use PHPStan\Type\Type;
use PHPStan\Type\VoidType;

class MagicSetterMethodReflection implements MethodReflectionWithNode
{

	/** @var ClassReflection */
	private $declaringClass;

	/** @var string */
	private $methodName;

	public function __construct(ClassReflection $declaringClass, string $methodName)
	{
		$this->declaringClass = $declaringClass;
		$this->methodName = $methodName;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return false;
	}

	public function isPrivate(): bool
	{
		return false;
	}

	public function isPublic(): bool
	{
		return true;
	}

	public function getDocComment(): ?string
	{
		return null;
	}

	public function getName(): string
	{
		return $this->methodName;
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this;
	}

	public function getVariants(): array
	{
		return [
			new FunctionVariant(
				TemplateTypeMap::createEmpty(),
				TemplateTypeMap::createEmpty(),
				[
					new DummyParameter(
						'name',
						new StringType(),
						false,
						null,
						false,
						null
					),
				],
				false,
				new VoidType()
			),
		];
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
		return TrinaryLogic::createNo();
	}

	public function getThrowType(): ?Type
	{
		return null;
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return TrinaryLogic::createYes();
	}

	public function getNode(): ClassMethod
	{
		return new ClassMethod(
			$this->methodName,
			[
				'flags' => Class_::MODIFIER_PUBLIC,
				'params' => [
					new Param(new Variable('name'), null, new Identifier('string')),
				],
				'returnType' => new Identifier('void'),
				'stmts' => [
					new Expression(new Assign(
						new PropertyFetch(
							new Variable('this'),
							new Identifier('name')
						),
						new Variable('name')
					)),
				],
			]
		);
	}

}
