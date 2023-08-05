<?php

namespace Smartosc\Customer\Model\Attribute;

use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Lookup\CRMData;

class CRMGender extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
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
     * @param CRMData $crmData
     * @param Authentication $authentication
     */
    public function __construct(
        CRMData $crmData,
        Authentication $authentication
    ) {
        $this->crmData = $crmData;
        $this->authentication = $authentication;
    }

    /**
     * @return array|array[]|null
     */
    public function getAllOptions(): ?array
    {
        if (!$this->_options) {
            $token = $this->authentication->getTokenAuthenticate();
            $crmGender = $this->crmData->getListDataLookup($token, 'GENDER');
            $options = [];
            foreach ($crmGender as $gender) {
                $beverages['value'] = $gender['TypeCode'];
                $beverages['label'] = $gender['TypeCodeValue'];
                $options[] = $beverages;
            }
            $this->_options = $options;
        }

        return $this->_options;
    }
}
