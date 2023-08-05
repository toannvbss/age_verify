<?php

namespace Smartosc\Customer\Block\Widget;

use Magento\Customer\Api\CustomerMetadataInterface;
use Magento\Customer\Block\Widget\AbstractWidget;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\CRM\Model\Authentication;

class PersonalCode extends AbstractWidget
{
    /**
     * @var CrmProfile
     */
    protected $crmProfile;

    /**
     * @var Authentication
     */
    protected $authentication;

    public function __construct(
        \Magento\Framework\View\Element\Template\Context $context,
        \Magento\Customer\Helper\Address $addressHelper,
        CustomerMetadataInterface $customerMetadata,
        CrmProfile $crmProfile,
        Authentication $authentication,
        array $data = []
    )
    {
        parent::__construct(
            $context,
            $addressHelper,
            $customerMetadata,
            $data
        );
        $this->crmProfile = $crmProfile;
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
        $this->setTemplate('Smartosc_Customer::widget/personal_code.phtml');
    }

    /**
     * @return mixed|string
     */
    public function getPersonalCodeValue() {
        $email = $this->getData('customer_email');
        $token = $this->authentication->getTokenAuthenticate();
        $params = ["EmailAddress" => $email];
        $result = $this->crmProfile->searchSimpleProfileByEmail($token, $params);
        if (isset($result[0]['PersonalCode'])) {
            return $result[0]['PersonalCode'];
        }
        return '';
    }

}
