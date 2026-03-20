<?php

namespace ConstantParameterCheckInstantiation;

// finfo::__construct - correct
new \finfo(FILEINFO_MIME);

// finfo::__construct - correct bitmask
new \finfo(FILEINFO_MIME_TYPE | FILEINFO_MIME_ENCODING);

// finfo::__construct - wrong constant
new \finfo(SORT_REGULAR);

// IntlDateFormatter::__construct - correct
new \IntlDateFormatter('en_US', \IntlDateFormatter::FULL, \IntlDateFormatter::SHORT);

// IntlDateFormatter::__construct - wrong constant for $dateType
new \IntlDateFormatter('en_US', \IntlDateFormatter::GREGORIAN, \IntlDateFormatter::SHORT);
