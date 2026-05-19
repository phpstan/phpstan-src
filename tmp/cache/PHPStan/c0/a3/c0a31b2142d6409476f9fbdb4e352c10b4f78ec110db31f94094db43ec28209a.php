<?php declare(strict_types = 1);

// osfsl-/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/php-merge/src/PhpMerge/MergeConflict.php-PHPStan\BetterReflection\Reflection\ReflectionClass-PhpMerge\MergeConflict
return \PHPStan\Cache\CacheItem::__set_state(array(
   'variableKey' => 'v2-a9f52965066ba1e53c7192454dbd2416db6b2679b5051ae25f92ea47c7a34ba0-8.4.21-6.70.0.1',
   'data' => 
  array (
    'locatedSource' => 
    array (
      'class' => 'PHPStan\\BetterReflection\\SourceLocator\\Located\\LocatedSource',
      'data' => 
      array (
        'name' => 'PhpMerge\\MergeConflict',
        'filename' => '/home/runner/work/phpstan-src/phpstan-src/vendor/composer/../ondrejmirtes/php-merge/src/PhpMerge/MergeConflict.php',
      ),
    ),
    'namespace' => 'PhpMerge',
    'name' => 'PhpMerge\\MergeConflict',
    'shortName' => 'MergeConflict',
    'isInterface' => false,
    'isTrait' => false,
    'isEnum' => false,
    'isBackedEnum' => false,
    'modifiers' => 32,
    'docComment' => '/**
 * Class MergeConflict
 *
 * This represents a merge conflict it includes the lines of the original and
 * both variations as well as the index on the original text where the conflict
 * starts.
 */',
    'attributes' => 
    array (
    ),
    'startLine' => 22,
    'endLine' => 137,
    'startColumn' => 1,
    'endColumn' => 1,
    'parentClassName' => NULL,
    'implementsClassNames' => 
    array (
    ),
    'traitClassNames' => 
    array (
    ),
    'immediateConstants' => 
    array (
    ),
    'immediateProperties' => 
    array (
      'base' => 
      array (
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'name' => 'base',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The lines from the original.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 30,
        'endLine' => 30,
        'startColumn' => 5,
        'endColumn' => 20,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'remote' => 
      array (
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'name' => 'remote',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The conflicting line changes from the first source.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 37,
        'endLine' => 37,
        'startColumn' => 5,
        'endColumn' => 22,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'local' => 
      array (
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'name' => 'local',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The conflicting line changes from the second source.
 *
 * @var string[]
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 44,
        'endLine' => 44,
        'startColumn' => 5,
        'endColumn' => 21,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'baseLine' => 
      array (
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'name' => 'baseLine',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The line number in the original text.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 51,
        'endLine' => 51,
        'startColumn' => 5,
        'endColumn' => 24,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
      'mergedLine' => 
      array (
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'name' => 'mergedLine',
        'modifiers' => 2,
        'type' => NULL,
        'default' => NULL,
        'docComment' => '/**
 * The line number in the merged text.
 *
 * @var int
 */',
        'attributes' => 
        array (
        ),
        'startLine' => 58,
        'endLine' => 58,
        'startColumn' => 5,
        'endColumn' => 26,
        'isPromoted' => false,
        'declaredAtCompileTime' => true,
        'immediateVirtual' => false,
        'immediateHooks' => 
        array (
        ),
      ),
    ),
    'immediateMethods' => 
    array (
      '__construct' => 
      array (
        'name' => '__construct',
        'parameters' => 
        array (
          'base' => 
          array (
            'name' => 'base',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 33,
            'endColumn' => 37,
            'parameterIndex' => 0,
            'isOptional' => false,
          ),
          'remote' => 
          array (
            'name' => 'remote',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 40,
            'endColumn' => 46,
            'parameterIndex' => 1,
            'isOptional' => false,
          ),
          'local' => 
          array (
            'name' => 'local',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 49,
            'endColumn' => 54,
            'parameterIndex' => 2,
            'isOptional' => false,
          ),
          'baseLine' => 
          array (
            'name' => 'baseLine',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 57,
            'endColumn' => 65,
            'parameterIndex' => 3,
            'isOptional' => false,
          ),
          'mergedLine' => 
          array (
            'name' => 'mergedLine',
            'default' => NULL,
            'type' => NULL,
            'isVariadic' => false,
            'byRef' => false,
            'isPromoted' => false,
            'attributes' => 
            array (
            ),
            'startLine' => 74,
            'endLine' => 74,
            'startColumn' => 68,
            'endColumn' => 78,
            'parameterIndex' => 4,
            'isOptional' => false,
          ),
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * MergeConflict constructor.
 *
 * @param string[] $base
 *   The original lines where the conflict happened.
 * @param string[] $remote
 *   The conflicting line changes from the first source.
 * @param string[] $local
 *   The conflicting line changes from the second source.
 * @param int $baseLine
 *   The line number in the original text.
 * @param int $mergedLine
 *   The line number in the merged text.
 */',
        'startLine' => 74,
        'endLine' => 81,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpMerge',
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'currentClassName' => 'PhpMerge\\MergeConflict',
        'aliasName' => NULL,
      ),
      'getBase' => 
      array (
        'name' => 'getBase',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the base text of the conflict.
 *
 * @return string[]
 *   The array of lines which are involved in the conflict.
 */',
        'startLine' => 89,
        'endLine' => 92,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpMerge',
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'currentClassName' => 'PhpMerge\\MergeConflict',
        'aliasName' => NULL,
      ),
      'getRemote' => 
      array (
        'name' => 'getRemote',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the lines from the first text.
 *
 * @return string[]
 *   The array of lines from the first text involved in the conflict.
 */',
        'startLine' => 100,
        'endLine' => 103,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpMerge',
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'currentClassName' => 'PhpMerge\\MergeConflict',
        'aliasName' => NULL,
      ),
      'getLocal' => 
      array (
        'name' => 'getLocal',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the lines from the second text.
 *
 * @return string[]
 *   The array of lines from the first text involved in the conflict.
 */',
        'startLine' => 111,
        'endLine' => 114,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpMerge',
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'currentClassName' => 'PhpMerge\\MergeConflict',
        'aliasName' => NULL,
      ),
      'getBaseLine' => 
      array (
        'name' => 'getBaseLine',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the line number in the original text where the conflict starts.
 *
 * @return int
 *   The line number as in the original text.
 */',
        'startLine' => 122,
        'endLine' => 125,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpMerge',
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'currentClassName' => 'PhpMerge\\MergeConflict',
        'aliasName' => NULL,
      ),
      'getMergedLine' => 
      array (
        'name' => 'getMergedLine',
        'parameters' => 
        array (
        ),
        'returnsReference' => false,
        'returnType' => NULL,
        'attributes' => 
        array (
        ),
        'docComment' => '/**
 * Get the line number in the merged text where the conflict starts.
 *
 * @return int
 *   The line number in the merged text.
 */',
        'startLine' => 133,
        'endLine' => 136,
        'startColumn' => 5,
        'endColumn' => 5,
        'couldThrow' => false,
        'isClosure' => false,
        'isGenerator' => false,
        'isVariadic' => false,
        'modifiers' => 1,
        'namespace' => 'PhpMerge',
        'declaringClassName' => 'PhpMerge\\MergeConflict',
        'implementingClassName' => 'PhpMerge\\MergeConflict',
        'currentClassName' => 'PhpMerge\\MergeConflict',
        'aliasName' => NULL,
      ),
    ),
    'traitsData' => 
    array (
      'aliases' => 
      array (
      ),
      'modifiers' => 
      array (
      ),
      'precedences' => 
      array (
      ),
      'hashes' => 
      array (
      ),
    ),
  ),
));