<?php declare(strict_types = 1);

namespace RequireFileStreamWrapperRegistered;

require_once 'modulea://sites/default/modulea.php';

\stream_wrapper_register('modulea', \stdClass::class);

require_once 'modulea://sites/default/modulea.php';
require_once 'moduleb://sites/default/moduleb.php';
