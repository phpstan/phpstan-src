<?php

namespace Bug5741;

/**
 * @template T
 */
final class Result
{
    /** @var T */
    public $value;

    /**
     * @param T $value
     */
    public function __construct($value)
    {
        $this->value = $value;
    }
}


/** 
 * @return Result<int[]>
 */
function one()
{
    $ints = [1, 2];
	
	\PHPStan\dumpType($ints);
	return new Result($ints);    
}


/** 
 * @return Result<int[]>
 */
function two()
{
	$result = new Result([]);
	$result->value = [1];
	$result->value[] = 2;    
	
	\PHPStan\dumpType($result->value);
	return $result;    
}


/** 
 * @return int[]
 */
function three()
{
    $ints = [1, 2];	
	\PHPStan\dumpType($ints);
	return $ints;    
}
