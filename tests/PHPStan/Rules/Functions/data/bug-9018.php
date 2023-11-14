<?php declare(strict_types=1); // lint >= 8.0

namespace Bug9018;

// This works
echo levenshtein('test1', 'test2');

// This works but fails analysis
echo levenshtein(string1: 'test1', string2: 'test2');

// This passes analysis but throws an error
// Warning: Uncaught Error: Unknown named parameter $str1 in php shell code:1
echo levenshtein(str1: 'test1', str2: 'test2');
