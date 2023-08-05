<?php

namespace Smartosc\Customer\Block\Form;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Smartosc\Customer\Model\CustomerConfigProvider;

class Edit extends \Magento\Customer\Block\Form\Edit
{
    /**
     * @var CustomerConfigProvider
     */
    protected $customerConfigProvider;

    /**
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory
     * @param CustomerRepositoryInterface $customerRepository
     * @param AccountManagementInterface $customerAccountManagement
     * @param CustomerConfigProvider $customerConfigProvider
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Newsletter\Model\SubscriberFactory $subscriberFactory,
        CustomerRepositoryInterface $customerRepository,
        AccountManagementInterface $customerAccountManagement,
        CustomerConfigProvider $customerConfigProvider,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $customerSession,
            $subscriberFactory,
            $customerRepository,
            $customerAccountManagement,
            $data
        );
        $this->customerConfigProvider = $customerConfigProvider;
    }

    /**
     * @return mixed
     */
    public function isAllowedChangeEmail()
    {
        return $this->customerConfigProvider->isChangeEmailCustomer();
    }
}
