<?php

declare(strict_types=1);

namespace EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Service\V2020_10;

use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Schema\TickeosProductStorageNextAction;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Service\V2020_04\MobileServiceApi as MobileServiceApiV2020_04;
use EosUptrade\TICKeos\Core\Repository\ProductRepository;
use EosUptrade\TICKeos\Plugins\MobileService\MobileServiceProductTreeBuilder;
use EosUptrade\TICKeos\Plugins\MobileService\Schema\Tree;
use sfContext;
use stdClass;

class MobileServiceApi extends MobileServiceApiV2020_04
{
    public const string VERSION = '2020.10';

}
