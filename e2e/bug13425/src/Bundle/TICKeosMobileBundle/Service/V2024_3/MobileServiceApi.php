<?php

declare(strict_types=1);

namespace EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Service\V2024_3;

use Doctrine\ORM\EntityManagerInterface;
use EosUptrade\TICKeos\Bundle\EtsPaymentFacadeClientBundle\Helper\BrowserType\BrowserTypeConfiguration;
use EosUptrade\TICKeos\Bundle\OrderProcessBundle\Domain\ProcessLockService;
use EosUptrade\TICKeos\Bundle\TICKeosCoreBundle\Domain\AnonymousCustomerSessionHandler;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Authentication\CreateAccessToken;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Certificate\LegacyCertificateProvider;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Service\V2022_03\MobileServiceApi as MobileServiceApiV202203;
use EosUptrade\TICKeos\Core\Customer\CustomerDataCompletionCheckerInterface;
use EosUptrade\TICKeos\Core\Domain\Customer\CustomerDeviceManager;
use EosUptrade\TICKeos\Core\Repository\CustomerDeviceRepository;
use EosUptrade\TICKeos\Core\Repository\GenericRepository;
use EosUptrade\TICKeos\Core\Repository\OrderProductRepository;
use EosUptrade\TICKeos\Core\Service\CustomerAnonymisationService;
use EosUptrade\TICKeos\Core\UseCase\Order\ClaimOrderProduct\ClaimOrderProduct;
use EosUptrade\TICKeos\Library\TickeosContracts\Repository\CustomerRepository;
use EosUptrade\TICKeos\MajorCustomer\Domain\MandatoryFieldConfiguration\MandatoryFieldsChecker;
use EosUptrade\TICKeos\MajorCustomer\Repository\CustomerToMajorCustomerRepository;
use EosUptrade\TICKeos\Shop\Domain\Customer\CustomerDisabledByChecker;
use MobileServiceClient;
use Psr\Log\LoggerInterface;
use RuntimeException;
use sfUser;
use stdClass;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MobileServiceApi extends MobileServiceApiV202203
{
    public const string VERSION = '2024.3';

    public function __construct(
        sfUser $user,
        AuthorizationCheckerInterface $authorizationChecker,
        LegacyCertificateProvider $certificateProvider,
        MobileServiceClient $client,
        AnonymousCustomerSessionHandler $anonymousCustomerSessionHandler,
        CustomerRepository $customerRepository,
        CustomerDisabledByChecker $customerDisabledByChecker,
        LoggerInterface $logger,
        ?BrowserTypeConfiguration $browserTypeConfiguration,
        CustomerToMajorCustomerRepository $customerToMajorCustomerRepository,
        CustomerDeviceRepository $customerDeviceRepository,
        CustomerDeviceManager $customerDeviceManager,
        GenericRepository $genericRepository,
        CreateAccessToken $createAccessToken,
        TranslatorInterface $translator,
        ClaimOrderProduct $claimOrderProduct,
        OrderProductRepository $orderProductRepository,
        EntityManagerInterface $entityManager,
        MandatoryFieldsChecker $mandatoryFieldsChecker,
        ProcessLockService $processLockService,
        CustomerAnonymisationService $customerAnonymisationService,
        bool $allowCustomerSelfAnonymisation,
        protected CustomerDataCompletionCheckerInterface $customerDataCompletionChecker,
    ) {
        parent::__construct(
            $user,
            $authorizationChecker,
            $certificateProvider,
            $client,
            $anonymousCustomerSessionHandler,
            $customerRepository,
            $customerDisabledByChecker,
            $logger,
            $browserTypeConfiguration,
            $customerToMajorCustomerRepository,
            $customerDeviceRepository,
            $customerDeviceManager,
            $genericRepository,
            $createAccessToken,
            $translator,
            $claimOrderProduct,
            $orderProductRepository,
            $entityManager,
            $mandatoryFieldsChecker,
            $processLockService,
            $customerAnonymisationService,
            $allowCustomerSelfAnonymisation
        );
    }

}
