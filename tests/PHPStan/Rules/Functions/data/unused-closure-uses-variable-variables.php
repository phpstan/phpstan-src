<?php

namespace UnusedClosureUsesVariableVariables;

function translate(string $value): \Closure
{
	$fromTranslations = [];
	$toTranslations = [];
	foreach (['from', 'to'] as $key) {
		$translationKey = $key . 'Translations';
		$$translationKey = [$value];
	}

	return function (string $chunk) use ($fromTranslations, $toTranslations): string {
		foreach ($fromTranslations as $index => $word) {
			if (preg_match("/^$word\$/iu", $chunk)) {
				return $toTranslations[$index] ?? '';
			}
		}
		return $chunk;
	};
}
