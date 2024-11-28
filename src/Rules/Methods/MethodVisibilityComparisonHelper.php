<?php declare(strict_types = 1);

namespace PHPStan\Rules\Methods;

use PHPStan\Reflection\ClassReflection;
use PHPStan\Reflection\ExtendedMethodReflection;
use PHPStan\Reflection\Php\PhpMethodFromParserNodeReflection;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\RuleErrorBuilder;
use function sprintf;

final class MethodVisibilityComparisonHelper
{

	/** @return list<IdentifierRuleError> */
	public function compare(ExtendedMethodReflection $prototype, ClassReflection $prototypeDeclaringClass, PhpMethodFromParserNodeReflection $method): array
	{
		/** @var list<IdentifierRuleError> $messages */
		$messages = [];

		if ($prototype->isPublic()) {
			if (!$method->isPublic()) {
				$messages[] = RuleErrorBuilder::message(sprintf(
					'%s method %s::%s() overriding public method %s::%s() should also be public.',
					$method->isPrivate() ? 'Private' : 'Protected',
					$method->getDeclaringClass()->getDisplayName(),
					$method->getName(),
					$prototypeDeclaringClass->getDisplayName(true),
					$prototype->getName(),
				))
					->nonIgnorable()
					->identifier('method.visibility')
					->build();
			}
		} elseif ($method->isPrivate()) {
			$messages[] = RuleErrorBuilder::message(sprintf(
				'Private method %s::%s() overriding protected method %s::%s() should be protected or public.',
				$method->getDeclaringClass()->getDisplayName(),
				$method->getName(),
				$prototypeDeclaringClass->getDisplayName(true),
				$prototype->getName(),
			))
				->nonIgnorable()
				->identifier('method.visibility')
				->build();
		}

		return $messages;
	}

}
