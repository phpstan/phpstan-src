#!/usr/bin/env php
<?php declare(strict_types=1);

(function () {
	require_once __DIR__ . '/../vendor/autoload.php';

	$template = <<<'php'
<?php declare(strict_types = 1);

namespace PHPStan\Rules\RuleErrors;

/**
 * @internal Use PHPStan\Rules\RuleErrorBuilder instead.
 */
class RuleError%s implements %s
{

	%s

	%s

}

php;
;

	$ruleErrorTypes = \PHPStan\Rules\RuleErrorBuilder::getRuleErrorTypes();
	$maxTypeNumber = array_sum(array_keys($ruleErrorTypes));
	foreach (range(1, $maxTypeNumber) as $typeCombination) {
		if (($typeCombination & 1) !== 1) {
			continue;
		}
		$properties = [];
		$interfaces = [];
		foreach ($ruleErrorTypes as $typeNumber => [$interface, $propertyName, $propertyType]) {
			if (($typeCombination & $typeNumber) === $typeNumber) {
				$properties[] = [$propertyName, $propertyType];
				$interfaces[] = '\\' . $interface;
			}
		}

		$phpClass = sprintf(
			$template,
			$typeCombination,
			implode(', ', $interfaces),
			implode("\n\n\t", array_map(function (array $property): string {
				return sprintf("/** @var %s */\n\tpublic $%s;", $property[1], $property[0]);
			}, $properties)),
			implode("\n\n\t", array_map(function (array $property): string {
				return sprintf("public function get%s(): %s\n\t{\n\t\treturn \$this->%s;\n\t}", ucfirst($property[0]), $property[1], $property[0]);
			}, $properties))
		);

		file_put_contents(__DIR__ . '/../src/Rules/RuleErrors/RuleError' . $typeCombination . '.php', $phpClass);
	}
})();
