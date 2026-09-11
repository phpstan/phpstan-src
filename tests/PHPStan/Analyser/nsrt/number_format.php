<?php

use function PHPStan\Testing\assertType;

assertType('string', number_format(1002.7));
assertType('non-decimal-int-string', number_format(1002.7, 3));
assertType('non-decimal-int-string', number_format(1002.7, 3, null));
assertType('non-decimal-int-string', number_format(1002.7, 3, '.'));
assertType('non-decimal-int-string', number_format(1002.7, 3, '.', ','));
assertType('non-decimal-int-string', number_format(1002.7, 3, '.', null));
assertType('string', number_format(1002.7, 3, '', null));
assertType('non-decimal-int-string', number_format(1002.7, 3, 'b', null));
assertType('non-decimal-int-string', number_format(1002.7, 3, 'b', ''));

assertType('non-decimal-int-string&numeric-string', number_format(1002.7, 3, '.', ''));
assertType('non-decimal-int-string&numeric-string', number_format(1002.7, 3, null, ''));
assertType('numeric-string', number_format(1002.7, 3, '', ''));

