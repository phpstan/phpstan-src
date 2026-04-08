<?php

namespace Bug14408;

// This should report an error: sprintf does not accept unknown named parameters
sprintf(format: '%s', foo: 'bar');

// This should be fine: named parameter matches the signature
sprintf(format: '%s %s', values: 'hello');

// call_user_func should accept unknown named args (forwards to callback)
call_user_func('strtolower', str: 'HELLO');
