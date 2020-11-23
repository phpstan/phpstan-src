<?php

// mb_str_split
/** @var string $string */
$string = doFoo();
$mbStrSplitConstantStringWithoutDefinedParameters = mb_str_split();
$mbStrSplitConstantStringWithoutDefinedSplitLength = mb_str_split('abcdef');
$mbStrSplitStringWithoutDefinedSplitLength = mb_str_split($string);
$mbStrSplitConstantStringWithOneSplitLength = mb_str_split('abcdef', 1);
$mbStrSplitConstantStringWithGreaterSplitLengthThanStringLength = mb_str_split('abcdef', 999);
$mbStrSplitConstantStringWithFailureSplitLength = mb_str_split('abcdef', 0);
$mbStrSplitConstantStringWithInvalidSplitLengthType = mb_str_split('abcdef', []);
$mbStrSplitConstantStringWithVariableStringAndConstantSplitLength = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', 1);
$mbStrSplitConstantStringWithVariableStringAndVariableSplitLength = mb_str_split(doFoo() ? 'abcdef' : 'ghijkl', doFoo() ? 1 : 2);
die;
