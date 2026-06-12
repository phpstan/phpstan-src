<?php // lint >= 8.1

namespace ConstantParameterCheckCallables;

// Callable from a function name - correct
$encode = json_encode(...);
$encode([], JSON_PRETTY_PRINT);

// Callable from a function name - wrong
$encode([], SORT_REGULAR);
