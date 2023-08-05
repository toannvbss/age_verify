<?php

namespace Smartosc\Customer\Model\Config\Source;

use Smartosc\Customer\Block\Widget\Telephone;

class TelephonePrefix extends \Magento\Eav\Model\Entity\Attribute\Source\AbstractSource
{
    /**
     * @var Telephone
     */
    protected $telephone;

    /**
     * @param Telephone $telephone
     */
    public function __construct(
        Telephone $telephone
    ) {
        $this->telephone = $telephone;
    }

    /**
     * @return array|null
     */
    public function getAllOptions(): ?array
    {
        if (!$this->_options) {
            $optionsWidget = $this->telephone->getPhoneCodeOptions();
            foreach ($optionsWidget as $code => $item) {
                $option['value'] = $code;
                $option['label'] = $item;
                $this->_options[] = $option;
            }
        }
        return $this->_options;
    }
}
