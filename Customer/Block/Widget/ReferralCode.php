<?php

namespace Smartosc\Customer\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
use Magento\Customer\Model\Session;

class ReferralCode extends AbstractWidget
{
    /**
     * @var \Magento\Customer\Model\Session
     */
    protected $_customerSession;

    /**
     * @var CustomerRepositoryInterface
     */
    protected $customerRepository;

    /**
     * ReferralCode constructor.
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param Session $_customerSession
     * @param CustomerRepositoryInterface $customerRepository
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Address $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        Session $_customerSession,
        CustomerRepositoryInterface $customerRepository,
        array $data = []
    )
    {
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
        $this->_customerSession  = $_customerSession;
        $this->customerRepository = $customerRepository;
    }

    public function _construct() {
        parent::_construct();
        $this->setTemplate('Smartosc_Customer::widget/referral_code.phtml');
    }

    public function getReferralCode() {
        if ($this->_customerSession->getCustomerId()) {
            $customer     = $this->customerRepository->getById($this->_customerSession->getCustomerId());
            $referralCodeAttribute = $customer->getCustomAttribute('referral_code');
            if ($referralCodeAttribute) {
                return $referralCodeAttribute->getValue();
            }
        }
        return '';
    }
}
