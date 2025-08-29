<?php

use EosUptrade\TICKeos\Bundle\EtsPaymentFacadeClientBundle\Helper\BrowserType\BrowserTypeConfiguration;
use EosUptrade\TICKeos\Bundle\TICKeosCoreBundle\Domain\AnonymousCustomerSessionHandler;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Authentication\CreateAccessToken;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Domain\Certificate\LegacyCertificateProvider;
use EosUptrade\TICKeos\Bundle\TICKeosMobileBundle\Exception\MobileServiceException;
use EosUptrade\TICKeos\Core\Domain\Customer\CustomerDeviceManager;
use EosUptrade\TICKeos\Core\License\License;
use EosUptrade\TICKeos\Core\Repository\CustomerDeviceRepository;
use EosUptrade\TICKeos\Core\Repository\GenericRepository;
use EosUptrade\TICKeos\Core\Repository\OrderProductRepository;
use EosUptrade\TICKeos\Core\UseCase\Order\ClaimOrderProduct\ClaimOrderProduct;
use EosUptrade\TICKeos\Core\UseCase\Order\ClaimOrderProduct\ClaimOrderProductException;
use EosUptrade\TICKeos\Core\UseCase\Order\ClaimOrderProduct\ClaimOrderProductRequest;
use EosUptrade\TICKeos\Library\TickeosContracts\Repository\CustomerRepository;
use EosUptrade\TICKeos\MajorCustomer\Repository\CustomerToMajorCustomerRepository;
use EosUptrade\TICKeos\Shop\Domain\Customer\CustomerDisabledByChecker;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class MobileServiceApi201806 extends MobileServiceApi201803
{
    public const string VERSION = '2018.06';

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
        protected CustomerDeviceRepository $customerDeviceRepository,
        protected CustomerDeviceManager $customerDeviceManager,
        protected GenericRepository $genericRepository,
        protected CreateAccessToken $createAccessToken,
        protected TranslatorInterface $translator,
        protected ClaimOrderProduct $claimOrderProduct,
        protected OrderProductRepository $orderProductRepository,
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
            $genericRepository
        );
    }


}
