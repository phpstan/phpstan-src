<?php declare(strict_types = 1);

namespace PHPStan\Rules;

use PHPStan\Classes\ForbiddenClassNameExtension;
use PHPStan\DependencyInjection\Container;
use function array_map;
use function array_merge;
use function sprintf;
use function str_starts_with;
use function strlen;
use function strpos;
use function substr;

final class ClassForbiddenNameCheck
{

	private const INTERNAL_CLASS_PREFIXES = [
		'PHPStan' => '_PHPStan_',
		'Rector' => 'RectorPrefix',
		'PHP-Scoper' => '_PhpScoper',
		'PHPUnit' => 'PHPUnitPHAR',
		'Box' => '_HumbugBox',
	];

	public function __construct(private Container $container)
	{
	}

	/**
	 * @param ClassNameNodePair[] $pairs
	 * @return list<IdentifierRuleError>
	 */
	public function checkClassNames(array $pairs): array
	{
		$extensions = $this->container->getServicesByTag(ForbiddenClassNameExtension::EXTENSION_TAG);

		$classPrefixes = array_merge(
			self::INTERNAL_CLASS_PREFIXES,
			...array_map(
				static fn (ForbiddenClassNameExtension $extension): array => $extension->getClassPrefixes(),
				$extensions,
			),
		);

		$errors = [];
		foreach ($pairs as $pair) {
			$className = $pair->getClassName();

			$projectName = null;
			$withoutPrefixClassName = null;
			foreach ($classPrefixes as $project => $prefix) {
				if (!str_starts_with($className, $prefix)) {
					continue;
				}

				$projectName = $project;
				$withoutPrefixClassName = substr($className, strlen($prefix));

				if (strpos($withoutPrefixClassName, '\\') === false) {
					continue;
				}

				$withoutPrefixClassName = substr($withoutPrefixClassName, strpos($withoutPrefixClassName, '\\'));
			}

			if ($projectName === null) {
				continue;
			}

			$error = RuleErrorBuilder::message(sprintf(
				'Referencing prefixed %s class: %s.',
				$projectName,
				$className,
			))
				->line($pair->getNode()->getLine())
				->identifier('class.prefixed')
				->nonIgnorable();

			if ($withoutPrefixClassName !== null) {
				$error->tip(sprintf(
					'This is most likely unintentional. Did you mean to type %s?',
					$withoutPrefixClassName,
				));
			}

			$errors[] = $error->build();
		}

		return $errors;
	}

}
