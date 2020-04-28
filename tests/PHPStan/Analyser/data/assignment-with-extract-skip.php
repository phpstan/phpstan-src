<?php

$headline = 'Test';

$skip = [
'headline' => 'Test Skip headline',
'new' => 'Test Skip new',
];

extract($skip, EXTR_SKIP);

assertType('\'Test\'', $headline);
assertType('\'Test Skip new\'', $new);
