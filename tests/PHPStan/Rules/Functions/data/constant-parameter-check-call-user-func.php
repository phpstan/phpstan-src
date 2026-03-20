<?php // lint >= 8.0

namespace ConstantParameterCheckCallUserFunc;

// call_user_func with correct constant
call_user_func('json_encode', [], JSON_PRETTY_PRINT);

// call_user_func with wrong constant
call_user_func('json_encode', [], SORT_REGULAR);
