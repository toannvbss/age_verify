<?php

namespace Smartosc\Customer\Controller\Account;

use Magento\Customer\Api\AccountManagementInterface;
use Magento\Customer\Model\Session;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Escaper;
use Smartosc\CRM\Model\Authentication;
use Smartosc\CRM\Model\Customer\CrmProfile;
use Smartosc\CRM\Model\DataProvider\RequestOptionsBuilder;
use Magento\Customer\Model\ResourceModel\CustomerRepository;

class ForgotPasswordPost extends \Magento\Customer\Controller\Account\ForgotPasswordPost
{
    /**
     * @var CrmProfile
     */
    protected $crmProfile;

    /**
     * @var Authentication
     */
    protected $crmAuthentication;

    /**
     * @var RequestOptionsBuilder
     */
    protected $requestOptionBuilder;

    /**
     * @var CustomerRepository
     */
    protected $customerRepository;

    /**
     * ForgotPasswordPost constructor.
     *
     * @param Context                    $context
     * @param Session                    $customerSession
     * @param AccountManagementInterface $customerAccountManagement
     * @param Escaper                    $escaper
     * @param CrmProfile                 $crmProfile
     * @param Authentication             $crmAuthentication
     * @param RequestOptionsBuilder      $requestOptionBuilder
     * @param CustomerRepository         $customerRepository
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        AccountManagementInterface $customerAccountManagement,
        Escaper $escaper,
        CrmProfile $crmProfile,
        Authentication $crmAuthentication,
        RequestOptionsBuilder $requestOptionBuilder,
        CustomerRepository $customerRepository
    ) {
        $this->crmProfile = $crmProfile;
        $this->crmAuthentication = $crmAuthentication;
        $this->requestOptionBuilder = $requestOptionBuilder;
        $this->customerRepository = $customerRepository;
        parent::__construct($context, $customerSession, $customerAccountManagement, $escaper);
    }

    /**
     * Forgot customer password action
     *
     * @return \Magento\Framework\Controller\Result\Redirect
     * @throws \Zend_Validate_Exception|\Magento\Framework\Exception\LocalizedException
     */
    public function execute()
    {
        $resultRedirect = $this->resultRedirectFactory->create();
        $email = (string)$this->getRequest()->getPost('email');
        if ($email) {
            if (!\Zend_Validate::is($email, \Magento\Framework\Validator\EmailAddress::class)) {
                $this->session->setForgottenEmail($email);
                $this->messageManager->addErrorMessage(
                    __('The email address is incorrect. Verify the email address and try again.')
                );
                return $resultRedirect->setPath('*/*/forgotpassword');
            }

            $isEmailExist = $this->customerAccountManagement->isEmailAvailable($email);
            $token = $this->crmAuthentication->getTokenAuthenticate();
            if ($isEmailExist) {
                //Not exist
                $searchRequest = $this->requestOptionBuilder->buildSearchProfile($email);
                $profileData = $this->crmProfile->searchSimpleProfileByEmail($token, $searchRequest);
                if ($profileData) {
                    try {
                        $forgotPasswordData = $this->requestOptionBuilder->prepareDataForgotPassword($email);
                        $this->crmProfile->forgotPasswordCRM($token, $forgotPasswordData);
                    } catch (\Exception $e) {
                        $this->messageManager->addErrorMessage(__("Error when reset password !"));
                        return $resultRedirect->setPath('*/*/forgotpassword');
                    }
                } else {
                    $this->messageManager->addErrorMessage(__("Your email hasn't been registered account !"));
                    return $resultRedirect->setPath('*/*/forgotpassword');
                }
            } else {
                //Exist
                $customer = $this->customerRepository->get($email);
                if ($customer->getCustomAttribute('customer_number')) {
                    $forgotPasswordData = $this->requestOptionBuilder->prepareDataForgotPassword($email);
                    $this->crmProfile->forgotPasswordCRM($token, $forgotPasswordData);
                } else {
                    $searchRequest = $this->requestOptionBuilder->buildSearchProfile($email);
                    $profileData = $this->crmProfile->searchSimpleProfileByEmail($token, $searchRequest);
                    if ($profileData) {
                        $forgotPasswordData = $this->requestOptionBuilder->prepareDataForgotPassword($email);
                        $this->crmProfile->forgotPasswordCRM($token, $forgotPasswordData);
                    } else {
                        $requestCreate = $this->requestOptionBuilder->buildDataRequestCRMCreate($customer);
                        $this->crmProfile->createCRMProfile($token, $requestCreate);
                    }
                }
            }
            $this->messageManager->addSuccessMessage($this->getSuccessMessage($email));
            return $resultRedirect->setPath('*/*/');
        } else {
            $this->messageManager->addErrorMessage(__('Please enter your email.'));
            return $resultRedirect->setPath('*/*/forgotpassword');
        }
    }
}
