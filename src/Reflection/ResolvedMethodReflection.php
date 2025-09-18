<?php declare(strict_types = 1);

namespace PHPStan\Reflection;

use PHPStan\Reflection\Php\PhpMethodReflection;
use PHPStan\TrinaryLogic;
use PHPStan\Type\Generic\TemplateTypeHelper;
use PHPStan\Type\Generic\TemplateTypeMap;
use PHPStan\Type\Generic\TemplateTypeVariance;
use PHPStan\Type\Generic\TemplateTypeVarianceMap;
use PHPStan\Type\Type;
use function is_bool;

final class ResolvedMethodReflection implements ExtendedMethodReflection
{

	/** @var list<ExtendedParametersAcceptor>|null */
	private ?array $variants = null;

	/** @var list<ExtendedParametersAcceptor>|null */
	private ?array $namedArgumentVariants = null;

	private ?Assertions $asserts = null;

	private Type|false|null $selfOutType = false;

	public function __construct(
		private ExtendedMethodReflection $reflection,
		private TemplateTypeMap $resolvedTemplateTypeMap,
		private TemplateTypeVarianceMap $callSiteVarianceMap,
	)
	{
	}

	public function getName(): string
	{
		return $this->reflection->getName();
	}

	public function getPrototype(): ClassMemberReflection
	{
		return $this->reflection->getPrototype();
	}

	public function getVariants(): array
	{
		$variants = $this->variants;
		if ($variants !== null) {
			return $variants;
		}

		return $this->variants = $this->resolveVariants($this->reflection->getVariants());
	}

	public function getOnlyVariant(): ExtendedParametersAcceptor
	{
		return $this->getVariants()[0];
	}

	public function getNamedArgumentsVariants(): ?array
	{
		$variants = $this->namedArgumentVariants;
		if ($variants !== null) {
			return $variants;
		}

		$innerVariants = $this->reflection->getNamedArgumentsVariants();
		if ($innerVariants === null) {
			return null;
		}

		return $this->namedArgumentVariants = $this->resolveVariants($innerVariants);
	}

	/**
	 * @param ExtendedParametersAcceptor[] $variants
	 * @return list<ResolvedFunctionVariant>
	 */
	private function resolveVariants(array $variants): array
	{
		$result = [];
		foreach ($variants as $variant) {
			$result[] = new ResolvedFunctionVariantWithOriginal(
				$variant,
				$this->resolvedTemplateTypeMap,
				$this->callSiteVarianceMap,
				[],
			);
		}

		return $result;
	}

	public function getDeclaringClass(): ClassReflection
	{
		return $this->reflection->getDeclaringClass();
	}

	public function getDeclaringTrait(): ?ClassReflection
	{
		if ($this->reflection instanceof PhpMethodReflection) {
			return $this->reflection->getDeclaringTrait();
		}

		return null;
	}

	public function isStatic(): bool
	{
		return $this->reflection->isStatic();
	}

	public function isPrivate(): bool
	{
		return $this->reflection->isPrivate();
	}

	public function isPublic(): bool
	{
		return $this->reflection->isPublic();
	}

	public function getDocComment(): ?string
	{
		return $this->reflection->getDocComment();
	}

	public function isDeprecated(): TrinaryLogic
	{
		return $this->reflection->isDeprecated();
	}

	public function getDeprecatedDescription(): ?string
	{
		return $this->reflection->getDeprecatedDescription();
	}

	public function isFinal(): TrinaryLogic
	{
		return $this->reflection->isFinal();
	}

	public function isFinalByKeyword(): TrinaryLogic
	{
		return $this->reflection->isFinalByKeyword();
	}

	public function isInternal(): TrinaryLogic
	{
		return $this->reflection->isInternal();
	}

	public function isBuiltin(): TrinaryLogic
	{
		$builtin = $this->reflection->isBuiltin();
		if (is_bool($builtin)) {
			return TrinaryLogic::createFromBoolean($builtin);
		}

		return $builtin;
	}

	public function getThrowType(): ?Type
	{
		return $this->reflection->getThrowType();
	}

	public function hasSideEffects(): TrinaryLogic
	{
		return $this->reflection->hasSideEffects();
	}

	public function isPure(): TrinaryLogic
	{
		return $this->reflection->isPure();
	}

	public function getAsserts(): Assertions
	{
		return $this->asserts ??= $this->reflection->getAsserts()->mapTypes(fn (Type $type) => TemplateTypeHelper::resolveTemplateTypes(
			$type,
			$this->resolvedTemplateTypeMap,
			$this->callSiteVarianceMap,
			TemplateTypeVariance::createInvariant(),
		));
	}

	public function acceptsNamedArguments(): TrinaryLogic
	{
		return $this->reflection->acceptsNamedArguments();
	}

	public function getSelfOutType(): ?Type
	{
		if ($this->selfOutType === false) {
			$selfOutType = $this->reflection->getSelfOutType();
			if ($selfOutType !== null) {
				$selfOutType = TemplateTypeHelper::resolveTemplateTypes(
					$selfOutType,
					$this->resolvedTemplateTypeMap,
					$this->callSiteVarianceMap,
					TemplateTypeVariance::createInvariant(),
				);
			}

			$this->selfOutType = $selfOutType;
		}

		return $this->selfOutType;
	}

	public function returnsByReference(): TrinaryLogic
	{
		return $this->reflection->returnsByReference();
	}

	public function isAbstract(): TrinaryLogic
	{
		$abstract = $this->reflection->isAbstract();
		if (is_bool($abstract)) {
			return TrinaryLogic::createFromBoolean($abstract);
		}

		return $abstract;
	}

	public function getAttributes(): array
	{
		return $this->reflection->getAttributes();
	}

	public function mustUseReturnValue(): TrinaryLogic
	{
		return $this->reflection->mustUseReturnValue();
	}

}
