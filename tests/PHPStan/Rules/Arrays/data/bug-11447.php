<?php

namespace Bug11447;

function doFoo() {
	\assert( \array_key_exists( 'key1', $_GET ) && \is_numeric( $_GET['key1'] ) );
	\assert( isset( $_GET['key2'] ) && \is_numeric( $_GET['key2'] ) );
}
