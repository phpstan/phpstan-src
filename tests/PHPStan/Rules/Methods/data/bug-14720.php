<?php

declare(strict_types=1);

namespace Bug14720;

$collator1 = new \Collator('en');
$collator1->setAttribute(\Collator::NUMERIC_COLLATION, \Collator::ON);

$collator2 = collator_create('en');
assert($collator2 instanceof \Collator);
collator_set_attribute($collator2, \Collator::NUMERIC_COLLATION, \Collator::ON);
