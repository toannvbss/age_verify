<?php

namespace Smartosc\Customer\Block\Widget;

class Dob extends \Magento\Customer\Block\Widget\Dob
{
    /**
     * Return data-validate rules
     *
     * @return string
     */
    public function getHtmlExtraParams(): string
    {
        $firstDateLetter = substr(strtolower($this->getDateFormat()), 0, 1);
        if ($firstDateLetter == 'm') {
            $rule = 'validate-date';
        } else {
            $rule = 'validate-date-au';
        }
        $extraParams = [
            "'" . $rule . "':true"
        ];
        if ($this->isRequired()) {
            $extraParams[] = 'required:true';
        }
        $extraParams[] = 'age_verify:true';
        $extraParams = implode(', ', $extraParams);
        return 'data-validate="{' . $extraParams . '}"';
    }
}
