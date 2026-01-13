<?php

$payload = 'b:0;';

unserialize($payload, ['allowed_classes' => [null]]);

unserialize($payload, ['allowed_classes' => null]);

unserialize($payload, ['max_depth' => null]);

unserialize($payload, ['foo' => null]);

unserialize($payload, ['allowed_classes' => true]);

unserialize($payload);
