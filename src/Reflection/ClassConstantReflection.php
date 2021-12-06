<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\BetterReflection\NodeCompiler\Exception\UnableToCompileNode;
use PHPStan\Php\PhpVersion;
use PHPStan\TrinaryLogic;
use PHPStan\Type\ConstantTypeHelper;
use PHPStan\Type\Type;
use ReflectionClassConstant;
use function method_exists;
use const NAN;

class ClassConstantReflection implements ConstantReflection
{

	private ClassReflection $declaringClass;

	private ReflectionClassConstant $reflection;

	private ?Type $phpDocType;

	private PhpVersion $phpVersion;

	private ?string $deprecatedDescription;

	private bool $isDeprecated;

	private bool $isInternal;

	private ?Type $valueType = null;

	public function __construct(
		ClassReflection $declaringClass,
		ReflectionClassConstant $reflection,
		?Type $phpDocType,
		PhpVersion $phpVersion,
		?string $deprecatedDescription,
		bool $isDeprecated,
		bool $isInternal
	)
	{
		$this->declaringClass = $declaringClass;
		$this->reflection = $reflection;
		$this->phpDocType = $phpDocType;
		$this->phpVersion = $phpVersion;
		$this->deprecatedDescription = $deprecatedDescription;
		$this->isDeprecated = $isDeprecated;
		$this->isInternal = $isInternal;
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
	 * @return mixed
	 */
	public function getValue()
	{
		try {
			return $this->reflection->getValue();
		} catch (UnableToCompileNode $e) {
			return NAN;
		}
	}

	public function hasPhpDocType(): bool
	{
		return $this->phpDocType !== null;
	}

	public function getValueType(): Type
	{
		if ($this->valueType === null) {
			if ($this->phpDocType === null) {
				$this->valueType = ConstantTypeHelper::getTypeFromValue($this->getValue());
			} else {
				$this->valueType = $this->phpDocType;
			}
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
		if (method_exists($this->reflection, 'isFinal')) {
			return $this->reflection->isFinal();
		}

		if (!$this->phpVersion->isInterfaceConstantImplicitlyFinal()) {
			return false;
		}

		return $this->declaringClass->isInterface();
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
