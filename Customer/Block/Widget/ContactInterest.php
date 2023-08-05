<?php

namespace Smartosc\Customer\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Customer\Api\Data\CustomerInterface;
use Magento\Customer\Api\Data\OptionInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Lookup\CRMData;

class ContactInterest extends AbstractWidget
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
        $this->setTemplate('Smartosc_Customer::widget/contactInterest.phtml');
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
     * Check if gender attribute enabled in system
     *
     * @return bool
     */
    public function isEnabled()
    {
        return $this->_getAttribute('contact_interest') && (bool)$this->_getAttribute('contact_interest')->isVisible();
    }

    /**
     * @return array
     */
    public function getListContactInterest(): array
    {
        $token = $this->authentication->getTokenAuthenticate();
        $crmContactInterest = $this->crmData->getListDataLookup($token, 'CONTACTINTEREST');
        if ($crmContactInterest) {
            $options = [];
            foreach ($crmContactInterest as $contactInterest) {
                if (strpos($contactInterest['TypeCode'], 'ASSOCIATE') === false) {
                    $contactInterests['value'] = $contactInterest['TypeCode'];
                    $contactInterests['label'] = $contactInterest['TypeCodeValue'];
                    $options[] = $contactInterests;
                }
            }
            return $options;
        }
        return [];
    }
}
