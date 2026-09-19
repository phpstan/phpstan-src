<?php // phpcs:ignoreFile

/**
 * Copied over from https://github.com/phan/phan/blob/8866d6b98be94b37996390da226e8c4befea29aa/src/Phan/Language/Internal/FunctionSignatureMap_php80_delta.php
 * Copyright (c) 2015 Rasmus Lerdorf
 * Copyright (c) 2015 Andrew Morrison
 */

/**
 * This contains the information needed to convert the function signatures for php 8.0 to php 7.4 (and vice versa)
 *
 * This has two sections.
 * The 'new' section contains function/method names from FunctionSignatureMap (And alternates, if applicable) that do not exist in php7.4 or have different signatures in php 8.0.
 *   If they were just updated, the function/method will be present in the 'added' signatures.
 * The 'old' signatures contains the signatures that are different in php 7.4.
 *   Functions are expected to be removed only in major releases of php.
 *
 * @see FunctionSignatureMap.php
 *
 * @phan-file-suppress PhanPluginMixedKeyNoKey (read by Phan when analyzing this file)
 */
return [
	'new' => [
		'iterator_count' => ['0|positive-int', 'iterator'=>'iterable'],
		'iterator_to_array' => ['array', 'iterator'=>'iterable', 'use_keys='=>'bool'],
		'str_split' => ['list<string>', 'str'=>'string', 'split_length='=>'positive-int'],
		'Random\Engine\Mt19937::generate' => ['non-empty-string'],
		'Random\Engine\PcgOneseq128XslRr64::generate' => ['non-empty-string'],
		'Random\Engine\Secure::generate' => ['non-empty-string'],
		'Random\Engine\Xoshiro256StarStar::generate' => ['non-empty-string'],
		'Random\Randomizer::getBytes' => ['non-empty-string', 'length'=>'positive-int'],
		'Random\Randomizer::nextInt' => ['int<0, max>'],
		'Random\Randomizer::pickArrayKeys' => ['non-empty-list<int|string>', 'array'=>'non-empty-array', 'num'=>'positive-int'],
	],
	'old' => [

	]
];
