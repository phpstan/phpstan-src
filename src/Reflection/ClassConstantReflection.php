<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PhpParser\Node\Expr;
use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\BetterReflection\Reflection\Adapter\ReflectionClassConstant;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Type;
use PHPStan\Type\TypehintHelper;
use const NAN;

/**
 * @api
 * @final
 */
class ClassConstantReflection implements ConstantReflection
{

	private ?Type $valueType = null;

	public function __construct(
		private InitializerExprTypeResolver $initializerExprTypeResolver,
		private ClassReflection $declaringClass,
		private ReflectionClassConstant $reflection,
		private ?Type $nativeType,
		private ?Type $phpDocType,
		private ?string $deprecatedDescription,
		private bool $isDeprecated,
		private bool $isInternal,
	)
	{
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getFileName(): ?string
	{
		return $this->declaringClass->getFileName();
	}

	/**
	 * @deprecated Use getValueExpr()
	 * @return mixed
	 */
	public function getValue()
	{
		try {
			return $this->reflection->getValue();
		} catch (UnableToCompileNode) {
			return NAN;
		}
	}

	public function getValueExpr(): Expr
	{
		return $this->reflection->getValueExpression();
	}

	public function hasPhpDocType(): bool
	{
		return $this->phpDocType !== null;
	}

	public function getPhpDocType(): ?Type
	{
		return $this->phpDocType;
	}

	public function hasNativeType(): bool
	{
		return $this->nativeType !== null;
	}

	public function getNativeType(): ?Type
	{
		return $this->nativeType;
	}

	public function getValueType(): Type
	{
		if ($this->valueType === null) {
			if ($this->phpDocType !== null) {
				if ($this->nativeType !== null) {
					return $this->valueType = TypehintHelper::decideType(
						$this->nativeType,
						$this->phpDocType,
					);
				}

				return $this->phpDocType;
			} elseif ($this->nativeType !== null) {
				return $this->nativeType;
			}

			$this->valueType = $this->initializerExprTypeResolver->getType($this->getValueExpr(), InitializerExprContext::fromClassReflection($this->declaringClass));
		}

		return $this->valueType;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->declaringClass;
	}

	public function isStatic(): bool
	{
		return true;
	}

	public function isPrivate(): bool
	{
		return $this->reflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->reflection->isPublic();
	}

	public function isFinal(): bool
	{
		return $this->reflection->isFinal();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isDeprecated);
	}

	public function getDeprecatedDescription(): ?string
	{
		if ($this->isDeprecated) {
			return $this->deprecatedDescription;
		}

		return null;
	}

	public function isInternal(): TrinaryLogic
	{
		return TrinaryLogic::createFromBoolean($this->isInternal);
	}

	public function getDocComment(): ?string
	{
		$docComment = $this->reflection->getDocComment();
		if ($docComment === false) {
			return null;
		}

		return $docComment;
	}

}
