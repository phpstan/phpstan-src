<?php

declare(strict_types=1);

namespace PHPStan\Rules\Methods;

use PhpParser\Node\Stmt\ClassMethod;
use PHPStan\Analyser\Scope;
use PHPStan\Rules\Rule;
use PHPStan\Rules\RuleError;
use PhpParser\Node;
use PHPStan\Rules\RuleErrorBuilder;

class ParameterRedefinitionRule implements Rule
{
    public function getNodeType(): string
    {
        return ClassMethod::class;
    }

    /**
     * @param ClassMethod $node
     *
     * @return array|RuleError[]|string[]
     */
    public function processNode(Node $node, Scope $scope): array
    {
        $errors = [];

        foreach ($node->params as $key => $param) {
            foreach ($node->params as $_key => $_param) {
                if ($key !== $_key && $param->var->name === $_param->var->name) {
                    $builder = RuleErrorBuilder::message(sprintf('Redefinition of parameter %s.', $param->var->name));

                    $errors[] = $builder->build();
                }
            }
        }

        return $errors;
    }
}