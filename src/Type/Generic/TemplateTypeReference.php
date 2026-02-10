<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

/**
 * A reference to a template type together with its variance at the point of usage.
 *
 * When a type contains template type parameters (e.g. `array<T>` or `Comparable<T>`),
 * this class pairs the TemplateType with its positional variance — whether T appears
 * in a covariant position (return type), contravariant position (parameter type),
 * invariant position, or bivariant position.
 *
 * Used by Type::getReferencedTemplateTypes() to report all template types within
 * a type along with their variance context. This information is used for:
 * - Template type inference (knowing the variance affects how types are inferred)
 * - Variance validation (checking that @template-covariant types only appear in
 *   covariant positions)
 */
final class TemplateTypeReference
{

	public function __construct(private TemplateType $type, private TemplateTypeVariance $positionVariance)
	{
	}

	public function getType(): TemplateType
	{
		return $this->type;
	}

	public function getPositionVariance(): TemplateTypeVariance
	{
		return $this->positionVariance;
	}

}
