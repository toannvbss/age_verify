<?php

namespace Smartosc\Customer\Plugin;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Controller\Account\EditPost;
use Magento\Framework\Exception\InputException;
use Smartosc\Customer\Model\CRMProfileManagement;

class IntegrationCRMBeforeSave
{
    /**
     * @var CRMProfileManagement
     */
    protected $CRMProfileManagement;

    /**
     * @param CRMProfileManagement $CRMProfileManagement
     */
    public function __construct(
        CRMProfileManagement $CRMProfileManagement
    ) {
        $this->CRMProfileManagement = $CRMProfileManagement;
    }

    /**
     * @param CustomerRepositoryInterface $repository
     * @param CustomerInterface $customer
     * @param null $passwordHash
     * @return array
     * @throws InputException
     */
    public function beforeSave(
        CustomerRepositoryInterface $repository,
        CustomerInterface $customer,
        $passwordHash = null
    ): array
    {
        if (isset($customer->getCustomAttributes()['form_code'])) {
            $customerFormCode = $customer->getCustomAttributes()['form_code'];
            if ($customerFormCode->getValue() == EditPost::FORM_DATA_EXTRACTOR_CODE) {
                try {
                    $this->CRMProfileManagement->updateCustomerCRMProfile($customer);
                } catch (InputException $e) {
                    throw new InputException(__($e->getMessage()), $e);
                }
            }
        }
        return [$customer, $passwordHash];
    }
}
