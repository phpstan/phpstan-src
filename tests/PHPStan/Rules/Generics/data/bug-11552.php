<?php

namespace Bug11552;

/**
 * @template TSuccess
 * @template TError
 */
class Result
{

}

/**
 * @extends Result<void, SomeResult::*>
 */
class SomeResult extends Result {

}
