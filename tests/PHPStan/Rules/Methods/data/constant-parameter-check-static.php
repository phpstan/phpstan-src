<?php

namespace ConstantParameterCheckStatic;

// IntlDateFormatter::create - correct
\IntlDateFormatter::create('en_US', \IntlDateFormatter::FULL, \IntlDateFormatter::SHORT);

// IntlDateFormatter::create - wrong constant for $dateType
\IntlDateFormatter::create('en_US', \IntlDateFormatter::GREGORIAN, \IntlDateFormatter::SHORT);

// NumberFormatter::create - correct
\NumberFormatter::create('en_US', \NumberFormatter::DECIMAL);

// NumberFormatter::create - wrong constant for $style
\NumberFormatter::create('en_US', \NumberFormatter::TYPE_INT32);
