<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Type;

/**
 * Template type strategy suitable for return type acceptance contexts
 */
class TemplateTypeArgumentStrategy implements TemplateTypeStrategy
{

	public function accepts(TemplateType $left, Type $right, bool $strictTypes): AcceptsResult
	{
		if ($right instanceof CompoundType) {
			$accepts = $right->isAcceptedWithReasonBy($left, $strictTypes);
		} else {
			$accepts = $left->getBound()->acceptsWithReason($right, $strictTypes)
				->and(AcceptsResult::createMaybe());
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
