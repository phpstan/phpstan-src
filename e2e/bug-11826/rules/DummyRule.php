<?php declare(strict_types = 1);

namespace Rules;

use App\FatalErrorWhenAutoloaded;
use PhpParser\Node;
use PHPStan\Analyser\Scope;
use PHPStan\Node\InClassNode;
use PHPStan\Rules\IdentifierRuleError;
use PHPStan\Rules\Rule;

/**
 * @implements Rule<InClassNode>
 */
class DummyRule implements Rule
{

    public function getNodeType(): string
    {
        return InClassNode::class;
    }

    /**
     * @param InClassNode $node
     * @return list<IdentifierRuleError>
     */
    public function processNode(
        Node $node,
        Scope $scope,
    ): array
    {
        return [FatalErrorWhenAutoloaded::AUTOLOAD];
    }

}
