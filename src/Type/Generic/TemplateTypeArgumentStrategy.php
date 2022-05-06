<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\TrinaryLogic;
use PHPStan\Type\CompoundType;
use PHPStan\Type\IntersectionType;
use PHPStan\Type\Type;

/**
 * Template type strategy suitable for return type acceptance contexts
 */
class TemplateTypeArgumentStrategy implements TemplateTypeStrategy
{

	public function accepts(TemplateType $left, Type $right, bool $strictTypes): TrinaryLogic
	{
		if ($right instanceof IntersectionType) {
			foreach ($right->getTypes() as $type) {
				if ($this->accepts($left, $type, $strictTypes)->yes()) {
					return TrinaryLogic::createYes();
				}
			}

			return TrinaryLogic::createNo();
		}

		if ($right instanceof CompoundType) {
			$accepts = $right->isAcceptedBy($left, $strictTypes);
		} else {
			$accepts = $left->getBound()->accepts($right, $strictTypes)
				->and(TrinaryLogic::createMaybe());
		}

		return $accepts;
	}

	public function isArgument(): bool
	{
		return true;
	}

	/**
	 * @param mixed[] $properties
	 */
	public static function __set_state(array $properties): self
	{
		return new self();
	}

}
