<?php

namespace ValueAssignedToDefine;

define('BAR_CONSTANT', false); // error
define('BAR_CONSTANT', rand(0,1) ? false : 1); // error - false|1 not assignable to int|string|null
define('BAR_CONSTANT', 1); // fine
define('BAR_CONSTANT', 'hello'); // fine
define('BAR_CONSTANT', null); // fine
define('BAR_CONSTANT', rand(0,1) ? 1 : 'hello'); // fine
define('OTHER_CONSTANT', false); // fine - not in dynamicConstantNames
