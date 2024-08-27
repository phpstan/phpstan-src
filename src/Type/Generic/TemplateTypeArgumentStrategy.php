<?php declare(strict_types = 1);

namespace PHPStan\Type\Generic;

use PHPStan\Type\AcceptsResult;
use PHPStan\Type\CompoundType;
use PHPStan\Type\Type;
use PHPStan\Type\VerbosityLevel;
use function array_merge;
use function sprintf;

/**
 * Template type strategy suitable for return type acceptance contexts
 */
final class TemplateTypeArgumentStrategy implements TemplateTypeStrategy
{

	public function accepts(TemplateType $left, Type $right, bool $strictTypes): AcceptsResult
	{
		if ($right instanceof CompoundType) {
			$accepts = $right->isAcceptedWithReasonBy($left, $strictTypes);
		} else {
			$accepts = $left->getBound()->acceptsWithReason($right, $strictTypes)
				->and(AcceptsResult::createMaybe());
			if ($accepts->maybe()) {
				$verbosity = VerbosityLevel::getRecommendedLevelByType($left, $right);

				return new AcceptsResult($accepts->result, array_merge($accepts->reasons, [
					sprintf(
						'Type %s is not always the same as %s. It breaks the contract for some argument types, typically subtypes.',
						$right->describe($verbosity),
						$left->getName(),
					),
				]));
			}
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
