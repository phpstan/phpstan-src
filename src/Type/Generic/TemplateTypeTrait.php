<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\PhpDocParser\Ast\Type\IdentifierTypeNode;
use PHPStan\PhpDocParser\Ast\Type\TypeNode;
use PHPStan\TrinaryLogic;
use PHPStan\Type\AcceptsResult;
use PHPStan\Type\ErrorType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\IsSuperTypeOfResult;
use PHPStan\Type\MixedType;
use PHPStan\Type\RecursionGuard;
use PHPStan\Type\SubtractableType;
use PHPStan\Type\Type;
use PHPStan\Type\TypeCombinator;
use PHPStan\Type\TypeUtils;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

/**
 * @template TBound of Type
 */
trait TemplateTypeTrait
{

	/** @var non-empty-string */
	private string $name;

	private TemplateTypeScope $scope;

	private TemplateTypeStrategy $strategy;

	private TemplateTypeVariance $variance;

	/** @var TBound */
	private Type $bound;

	private ?Type $default;

	/** @return non-empty-string */
	public function getName(): string
	{
		return $this->name;
	}

	public function getScope(): TemplateTypeScope
	{
		return $this->scope;
	}

	/** @return TBound */
	public function getBound(): Type
	{
		return $this->bound;
	}

	public function getDefault(): ?Type
	{
		return $this->default;
	}

	public function describe(VerbosityLevel $level): string
	{
		$basicDescription = function () use ($level): string {
			// @phpstan-ignore booleanAnd.alwaysFalse, instanceof.alwaysFalse, booleanAnd.alwaysFalse, instanceof.alwaysFalse, instanceof.alwaysTrue
			if ($this->bound instanceof MixedType && $this->bound->getSubtractedType() === null && !$this->bound instanceof TemplateMixedType) {
				$boundDescription = '';
			} else {
				$boundDescription = sprintf(' of %s', $this->bound->describe($level));
			}
			$defaultDescription = '';
			if ($this->default !== null) {
				$recursionGuard = RecursionGuard::runOnObjectIdentity($this->default, fn () => $this->default->describe($level));
				if (!$recursionGuard instanceof ErrorType) {
					$defaultDescription .= sprintf(' = %s', $recursionGuard);
				}
			}
			return sprintf(
				'%s%s%s',
				$this->name,
				$boundDescription,
				$defaultDescription,
			);
		};

		return $level->handle(
			$basicDescription,
			$basicDescription,
			fn (): string => sprintf('%s (%s, %s)', $basicDescription(), $this->scope->describe(), $this->isArgument() ? 'argument' : 'parameter'),
		);
	}

	public function isArgument(): bool
	{
		return $this->strategy->isArgument();
	}

	public function toArgument(): TemplateType
	{
		return new self(
			$this->scope,
			new TemplateTypeArgumentStrategy(),
			$this->variance,
			$this->name,
			TemplateTypeHelper::toArgument($this->getBound()),
			$this->default !== null ? TemplateTypeHelper::toArgument($this->default) : null,
		);
	}

	public function isValidVariance(Type $a, Type $b): IsSuperTypeOfResult
	{
		return $this->variance->isValidVariance($this, $a, $b);
	}

	public function subtract(Type $typeToRemove): Type
	{
		$removedBound = TypeCombinator::remove($this->getBound(), $typeToRemove);
		return TemplateTypeFactory::create(
			$this->getScope(),
			$this->getName(),
			$removedBound,
			$this->getVariance(),
			$this->getStrategy(),
			$this->getDefault(),
		);
	}

	public function getTypeWithoutSubtractedType(): Type
	{
		$bound = $this->getBound();
		if (!$bound instanceof SubtractableType) { // @phpstan-ignore instanceof.alwaysTrue
			return $this;
		}

		return TemplateTypeFactory::create(
			$this->getScope(),
			$this->getName(),
			$bound->getTypeWithoutSubtractedType(),
			$this->getVariance(),
			$this->getStrategy(),
			$this->getDefault(),
		);
	}

	public function changeSubtractedType(?Type $subtractedType): Type
	{
		$bound = $this->getBound();
		if (!$bound instanceof SubtractableType) { // @phpstan-ignore instanceof.alwaysTrue
			return $this;
		}

		return TemplateTypeFactory::create(
			$this->getScope(),
			$this->getName(),
			$bound->changeSubtractedType($subtractedType),
			$this->getVariance(),
			$this->getStrategy(),
			$this->getDefault(),
		);
	}

	public function getSubtractedType(): ?Type
	{
		$bound = $this->getBound();
		if (!$bound instanceof SubtractableType) { // @phpstan-ignore instanceof.alwaysTrue
			return null;
		}

		return $bound->getSubtractedType();
	}

	public function equals(Type $type): bool
	{
		return $type instanceof self
			&& $type->scope->equals($this->scope)
			&& $type->name === $this->name
			&& $this->bound->equals($type->bound)
			&& (
				($this->default === null && $type->default === null)
				|| ($this->default !== null && $type->default !== null && $this->default->equals($type->default))
			);
	}

	public function isAcceptedBy(Type $acceptingType, bool $strictTypes): AcceptsResult
	{
		/** @var TBound $bound */
		$bound = $this->getBound();
		if (
			!$acceptingType instanceof $bound
			&& !$this instanceof $acceptingType
			&& !$acceptingType instanceof TemplateType
			&& ($acceptingType instanceof UnionType || $acceptingType instanceof IntersectionType)
		) {
			return $acceptingType->accepts($this, $strictTypes);
		}

		if (!$acceptingType instanceof TemplateType) {
			return $acceptingType->accepts($this->getBound(), $strictTypes);
		}

		if ($this->getScope()->equals($acceptingType->getScope()) && $this->getName() === $acceptingType->getName()) {
			return $acceptingType->getBound()->accepts($this->getBound(), $strictTypes);
		}

		return $acceptingType->getBound()->accepts($this->getBound(), $strictTypes)
			->and(new AcceptsResult(TrinaryLogic::createMaybe(), []));
	}

	public function accepts(Type $type, bool $strictTypes): AcceptsResult
	{
		return $this->strategy->accepts($this, $type, $strictTypes);
	}

	public function isSuperTypeOf(Type $type): IsSuperTypeOfResult
	{
		if ($type instanceof TemplateType || $type instanceof IntersectionType) {
			return $type->isSubTypeOf($this);
		}

		if (!$type->isNever()->no()) {
			return IsSuperTypeOfResult::createYes();
		}

		return $this->getBound()->isSuperTypeOf($type)
			->and(IsSuperTypeOfResult::createMaybe());
	}

	public function isSubTypeOf(Type $type): IsSuperTypeOfResult
	{
		/** @var TBound $bound */
		$bound = $this->getBound();
		if (
			!$type instanceof $bound
			&& !$this instanceof $type
			&& !$type instanceof TemplateType
			&& ($type instanceof UnionType || $type instanceof IntersectionType)
		) {
			return $type->isSuperTypeOf($this);
		}

		if (!$type instanceof TemplateType) {
			return $type->isSuperTypeOf($this->getBound());
		}

		if ($this->getScope()->equals($type->getScope()) && $this->getName() === $type->getName()) {
			return $type->getBound()->isSuperTypeOf($this->getBound());
		}

		return $type->getBound()->isSuperTypeOf($this->getBound())
			->and(IsSuperTypeOfResult::createMaybe());
	}

	public function toArrayKey(): Type
	{
		return $this;
	}

	public function toCoercedArgumentType(bool $strictTypes): Type
	{
		return $this;
	}

	public function inferTemplateTypes(Type $receivedType): TemplateTypeMap
	{
		if (
			$receivedType instanceof TemplateType
			&& $this->getBound()->isSuperTypeOf($receivedType->getBound())->yes()
		) {
			return new TemplateTypeMap([
				$this->name => $receivedType,
			]);
		}

		$map = $this->getBound()->inferTemplateTypes($receivedType);
		$resolvedBound = TypeUtils::resolveLateResolvableTypes(TemplateTypeHelper::resolveTemplateTypes(
			$this->getBound(),
			$map,
			TemplateTypeVarianceMap::createEmpty(),
			TemplateTypeVariance::createStatic(),
		));
		if ($resolvedBound->isSuperTypeOf($receivedType)->yes()) {
			return (new TemplateTypeMap([
				$this->name => $receivedType,
			]))->union($map);
		}

		return $map;
	}

	public function getReferencedTemplateTypes(TemplateTypeVariance $positionVariance): array
	{
		return [new TemplateTypeReference($this, $positionVariance)];
	}

	public function getVariance(): TemplateTypeVariance
	{
		return $this->variance;
	}

	public function getStrategy(): TemplateTypeStrategy
	{
		return $this->strategy;
	}

	public function traverse(callable $cb): Type
	{
		$bound = $cb($this->getBound());
		$default = $this->getDefault() !== null ? $cb($this->getDefault()) : null;

		if ($this->getBound() === $bound && $this->getDefault() === $default) {
			return $this;
		}

		return TemplateTypeFactory::create(
			$this->getScope(),
			$this->getName(),
			$bound,
			$this->getVariance(),
			$this->getStrategy(),
			$default,
		);
	}

	public function traverseSimultaneously(Type $right, callable $cb): Type
	{
		if (!$right instanceof TemplateType) {
			return $this;
		}

		$bound = $cb($this->getBound(), $right->getBound());
		$default = $this->getDefault() !== null && $right->getDefault() !== null ? $cb($this->getDefault(), $right->getDefault()) : null;

		if ($this->getBound() === $bound && $this->getDefault() === $default) {
			return $this;
		}

		return TemplateTypeFactory::create(
			$this->getScope(),
			$this->getName(),
			$bound,
			$this->getVariance(),
			$this->getStrategy(),
			$default,
		);
	}

	public function tryRemove(Type $typeToRemove): ?Type
	{
		$bound = TypeCombinator::remove($this->getBound(), $typeToRemove);
		if ($this->getBound() === $bound) {
			return null;
		}

		return TemplateTypeFactory::create(
			$this->getScope(),
			$this->getName(),
			$bound,
			$this->getVariance(),
			$this->getStrategy(),
			$this->getDefault(),
		);
	}

	public function toPhpDocNode(): TypeNode
	{
		return new IdentifierTypeNode($this->name);
	}

}
