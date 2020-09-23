<?php

declare(strict_types=1);

$userStreamWrappers = $container->getParameter('streamWrappers');

foreach ($userStreamWrappers as $protocol => $streamWrapperFQCN) {
	spl_autoload_call($streamWrapperFQCN);
	stream_wrapper_unregister($protocol);
	stream_wrapper_register($protocol, $streamWrapperFQCN);
}
