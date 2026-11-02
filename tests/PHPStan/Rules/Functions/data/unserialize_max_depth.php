<?php

$payload = 'b:0;';

unserialize($payload, ['allowed_classes' => false, 'max_depth' => 3]);
