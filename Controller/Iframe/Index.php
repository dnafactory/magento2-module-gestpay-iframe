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

class Index extends Action implements HttpGetActionInterface, HttpPostActionInterface
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

        $pares = $this->getRequest()->getParam('PaRes', null);
        if (strlen($pares) > 0) {
            return $this->redirectToPayment();
        } else {
            $this->cleanCookie();
        }

        $orderId = $this->getRequest()->getParam('order_id', null);
        if (!$orderId) {
            return $this->redirectError(__("Empty Order ID"));
        }

        $order = $this->orderRepository->get($orderId);
        if (!$order || !$order->getEntityId()) {
            return $this->redirectError("Wrong Order ID");
        }

        // solo pending, solo gestpay
        if (!$order->getPayment() || $order->getPayment()->getMethod() !== 'easynolo_bancasellapro') {
            return $this->redirectError("Wrong Payment Method");
        }

        if ($order->getPayment()->getBaseAmountPaid() > 0) {
            return $this->redirectError("Order already Paid");
        }

        $shopLogin = $this->axerveConfiguration->getMerchantId();
        $wsdl = $this->axerveConfiguration->getUrlWsdl();
        $shopTransactionId = $order->getIncrementId();

        ///////////////////////
        /// PZ8
        /// baseGrandTotal refer to base currency
        /// workaround to avoid currency mapping
        ///
        $amount = $order->getBaseGrandTotal();
        $amount = number_format($amount, 2);
        $currency = $this->axerveConfiguration->getCurrency();
        ///
        ///////////////////////

        $client = new \SoapClient($wsdl, ['exceptions' => true]);

        $params = [
            'shopLogin' => $shopLogin,
            'uicCode' => $currency,
            'amount' => $amount,
            'shopTransactionId' => $shopTransactionId
        ];
        try {
            $response = $client->Encrypt($params);
        } catch (\SoapFault $e) {
            return $this->redirectError($e->faultstring);
        } catch (\Exception $e) {
            return $this->redirectError($e->getMessage());
        }

        $result = simplexml_load_string($response->EncryptResult->any);
        $errCode = (string) $result->ErrorCode;
        $errDesc = (string) $result->ErrorDescription;

        // Don't force triple check (===), never trust
        if ($errCode == '0') {
            $encString = (string) $result->CryptDecryptString;

            $metadata = $this->cookieMetadataFactory
                ->createPublicCookieMetadata()
                ->setDuration(86400)
                ->setPath($this->sessionManager->getCookiePath())
                ->setDomain($this->sessionManager->getCookieDomain());

            $this->cookieManager->setPublicCookie(
                'encString',
                $encString,
                $metadata
            );

            return $this->redirectToPayment();
        }

        return $this->redirectError($errDesc);
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

    protected function redirectToPayment()
    {
        $redirect = $this->resultFactory->create(ResultFactory::TYPE_REDIRECT);
        $redirect->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        $redirect->setHttpResponseCode(302); // Prevent Browser Caching, don't remove
        $redirect->setUrl('/bancasellaproiframe/iframe/payment');
        return $redirect;
    }
}
