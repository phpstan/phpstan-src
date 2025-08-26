<?php declare(strict_types = 1);

namespace PHPStan\Rules\TooWideTypehints;

use PHPStan\DependencyInjection\AutowiredService;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use PHPStan\Type\Type;
use PHPStan\Type\UnionType;
use PHPStan\Type\VerbosityLevel;
use function sprintf;

#[AutowiredService]
final class TooWideReturnTypeCheck
{

	/**
	 * @return list<IdentifierRuleError>
	 */
	public function checkAnonymousFunction(
		Type $returnType,
		UnionType $functionReturnType,
	): array
	{
		if ($returnType->isNull()->yes()) {
			return [];
		}
		$messages = [];
		foreach ($functionReturnType->getTypes() as $type) {
			if (!$type->isSuperTypeOf($returnType)->no()) {
				continue;
			}

			$messages[] = RuleErrorBuilder::message(sprintf(
				'%s never returns %s so it can be removed from the return type.',
				'Anonymous function',
				$type->describe(VerbosityLevel::getRecommendedLevelByType($type)),
			))->identifier('return.unusedType')->build();
		}

		return $messages;
	}

}
