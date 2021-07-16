<?php

namespace DNAFactory\BancaSellaProIframe\Controller\Iframe;

use DNAFactory\BancaSellaProIframe\Api\AxerveConfigurationInterface;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Stdlib\CookieManagerInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Framework\Session\SessionManagerInterface;

class Response extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    protected CookieManagerInterface $cookieManager;
    protected AxerveConfigurationInterface $axerveConfiguration;
    protected OrderRepositoryInterface $orderRepository;
    protected CookieMetadataFactory $cookieMetadataFactory;
    protected SessionManagerInterface $sessionManager;

    public function __construct(
        Context $context,
        CookieManagerInterface $cookieManager,
        OrderRepositoryInterface $orderRepository,
        AxerveConfigurationInterface $axerveConfiguration,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager,
        ManagerInterface $messageManager
    ) {
        parent::__construct($context);
        $this->cookieManager = $cookieManager;
        $this->axerveConfiguration = $axerveConfiguration;
        $this->orderRepository = $orderRepository;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->messageManager = $messageManager;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        if (!$this->axerveConfiguration->isActive()) {
            return $this->redirectError(__("Axerve disabled"));
        }

        $cryptedString = $this->getRequest()->getParam('crypted_string', null);
        if (!$cryptedString) {
            return $this->redirectError(__("Empty Encripted String"));
        }

        $shopLogin = $this->axerveConfiguration->getMerchantId();
        $wsdl = $this->axerveConfiguration->getUrlWsdl();

        $client = new \SoapClient($wsdl, ['exceptions' => true]);

        $params = [
            'shopLogin' => $shopLogin,
            'CryptedString' => $cryptedString
        ];

        try {
            $response = $client->Decrypt($params);
        } catch (\SoapFault $e) {
            return $this->redirectError($e->faultstring);
        } catch (\Exception $e) {
            return $this->redirectError($e->getMessage());
        }

        $result = simplexml_load_string($response->DecryptResult->any);

        $errCode = (string) $result->ErrorCode;
        $errorDescription = (string) $result->ErrorDescription;

        if ($errCode != "0") {
            $this->redirectError($errorDescription);
        }

        $this->cleanCookie();
        return $this->redirectSuccess();
    }

    protected function cleanCookie()
    {
        $this->cookieManager->deleteCookie('encString');
        $this->cookieManager->deleteCookie('TransKey');
        $this->cookieManager->deleteCookie('shopLogin');
    }

    protected function redirectError(string $error = '')
    {
        $this->cleanCookie();

        $this->messageManager->addErrorMessage($error);
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $redirect->setHttpResponseCode(302); // Prevent Browser Caching, don't remove
        $redirect->setUrl('/checkout/cart');
        return $redirect;
    }

    protected function redirectSuccess()
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $redirect->setHttpResponseCode(302); // Prevent Browser Caching, don't remove
        $redirect->setUrl('/bancasellaproiframe/iframe/success');
        return $redirect;
    }
}
