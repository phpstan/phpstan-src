<?php

const BAR_CONSTANT = false; // error
const OTHER_CONSTANT = false; // fine - not in dynamicConstantNames
const MAYBE_CONSTANT = DYNAMIC_INT_CONSTANT; // error (maybe) - positive-int doesn't fully accept int
const A_NON_EMPTY_STRING = '';
