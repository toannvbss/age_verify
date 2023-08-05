<?php

namespace Smartosc\Customer\Model\Config\Source;

class CRMGender implements \Magento\Framework\Data\OptionSourceInterface
{
    /**
     * @var \Smartosc\Customer\Block\Widget\Gender
     */
    protected $gender;

    /**
     * @param \Smartosc\Customer\Block\Widget\Gender $gender
     */
    public function __construct(
        \Smartosc\Customer\Block\Widget\Gender $gender
    ) {
        $this->gender = $gender;
    }

    /**
     * @return array|void
     */
    public function toOptionArray()
    {
        return $this->gender->getListGender();
    }
}
