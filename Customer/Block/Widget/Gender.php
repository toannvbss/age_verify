<?php

namespace Smartosc\Customer\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Lookup\CRMData;

class Gender extends \Magento\Customer\Block\Widget\Gender
{
    /**
     * @var CRMData
     */
    protected $crmData;
    /**
     * @var Authentication
     */
    protected $authentication;

    /**
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
        parent::__construct($context, $addressHelper, $customerMetadata, $customerRepository, $customerSession, $data);
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
        $this->setTemplate('Smartosc_Customer::widget/gender.phtml');
    }

    /**
     * @return array
     */
    public function getListGender(): array
    {
        $token = $this->authentication->getTokenAuthenticate();
        $crmGender = $this->crmData->getListDataLookup($token, 'GENDER');
        if ($crmGender) {
            $options = [];
            foreach ($crmGender as $gender) {
                $beverages['value'] = $gender['TypeCode'];
                $beverages['label'] = $gender['TypeCodeValue'];
                $options[] = $beverages;
            }
            return $options;
        }
        return [];
    }
}
