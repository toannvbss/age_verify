<?php

namespace Smartosc\Customer\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\OptionInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Lookup\CRMData;

class Beverages extends AbstractWidget
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
     * @var CRMData
     */
    protected $crmData;
    /**
     * @var Authentication
     */
    protected $authentication;

    /**
     * Create an instance of the Gender widget
     *
     * @param \Magento\Framework\View\Element\Template\Context $context
     * @param \Magento\Customer\Helper\Address $addressHelper
     * @param CustomerMetadataInterface $customerMetadata
     * @param CustomerRepositoryInterface $customerRepository
     * @param \Magento\Customer\Model\Session $customerSession
     * @param CRMData $crmData
     * @param Authentication $authentication
     * @param array $data
     */
    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Address $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        CustomerRepositoryInterface $customerRepository,
        \Magento\Customer\Model\Session $customerSession,
        CRMData $crmData,
        Authentication $authentication,
        array $data = []
    ) {
        $this->_customerSession = $customerSession;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $addressHelper, $customerMetadata, $data);
        $this->_isScopePrivate = true;
        $this->crmData = $crmData;
        $this->authentication = $authentication;
    }

    /**
     * Initialize block
     *
     * @return void
     */
    public function _construct()
    {
        parent::_construct();
        $this->setTemplate('Smartosc_Customer::widget/beverages.phtml');
    }

    /**
     * Check if gender attribute enabled in system
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_getAttribute('beverages_preference') ? (bool)$this->_getAttribute('beverages_preference')->isVisible() : false;
    }

    /**
     * Check if gender attribute marked as required
     *
     * @return bool
     */
    public function isRequired()
    {
        return $this->_getAttribute('beverages_preference') ? (bool)$this->_getAttribute('beverages_preference')->isRequired() : false;
    }

    /**
     * Retrieve store attribute label
     *
     * @param string $attributeCode
     *
     * @return string
     */
    public function getStoreLabel($attributeCode)
    {
        $attribute = $this->_getAttribute($attributeCode);
        return $attribute ? __($attribute->getStoreLabel()) : '';
    }

    /**
     * Get current customer from session
     *
     * @return CustomerInterface
     */
    public function getCustomer()
    {
        return $this->customerRepository->getById($this->_customerSession->getCustomerId());
    }

    /**
     * Returns options from gender attribute
     *
     * @return OptionInterface[]
     */
    public function getBeveragesOptions()
    {
        return $this->_getAttribute('beverages_preference')->getOptions();
    }

    /**
     * @return array
     */
    public function getListBeverages(): array
    {
        $token = $this->authentication->getTokenAuthenticate();
        $crmBeverages = $this->crmData->getListDataLookup($token, 'INTEREST');
        if ($crmBeverages) {
            $options = [];
            foreach ($crmBeverages as $beverage) {
                $beverages['value'] = $beverage['TypeCode'];
                $beverages['label'] = $beverage['TypeCodeValue'];
                $options[] = $beverages;
            }
            return $options;
        }
        return [];
    }
}
