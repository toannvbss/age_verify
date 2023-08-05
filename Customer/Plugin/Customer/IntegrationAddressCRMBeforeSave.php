<?php

namespace Smartosc\Customer\Plugin\Customer;

use Magento\Customer\Api\AddressRepositoryInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\AddressInterface;
use Magento\Framework\Exception\InputException;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Smartosc\Customer\Model\CRMProfileManagement;

class IntegrationAddressCRMBeforeSave
{
    /**
     * @var CRMProfileManagement
     */
    protected $CRMProfileManagement;
    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;
    /**
     * @var \Magento\Framework\Message\ManagerInterface
     */
    protected $messageManager;

    /**
     * @param CRMProfileManagement $CRMProfileManagement
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Framework\Message\ManagerInterface $messageManager
     */
    public function __construct(
        CRMProfileManagement $CRMProfileManagement,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Framework\Message\ManagerInterface $messageManager
    ) {
        $this->CRMProfileManagement = $CRMProfileManagement;
        $this->customerRepository = $customerRepository;
        $this->messageManager = $messageManager;
    }

    /**
     * @param AddressRepositoryInterface $subject
     * @param AddressInterface $address
     * @return AddressInterface[]
     * @throws InputException
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function beforeSave(
        AddressRepositoryInterface $subject,
        AddressInterface $address
    ): array
    {
        $customer = $this->customerRepository->getById((int)$address->getCustomerId());
        if (isset($customer->getCustomAttributes()['form_code'])
            && $customer->getCustomAttributes()['form_code']->getValue() == 'customer_account_login') {
            return [$address];
        }
        try {
            $this->CRMProfileManagement->updateCustomerCRMProfile($customer, $address);
        } catch (InputException $e) {
            throw new InputException(__($e->getMessage()), $e);
        }
        return [$address];
    }
}
