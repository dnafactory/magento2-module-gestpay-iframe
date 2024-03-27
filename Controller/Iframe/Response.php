<?php

namespace DNAFactory\BancaSellaProIframe\Controller\Iframe;

use DNAFactory\BancaSellaProIframe\Exception\CryptedStringException;
use DNAFactory\BancaSellaProIframe\Exception\InvalidOrderIdException;
use DNAFactory\BancaSellaProIframe\Helper\Data;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class Response extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    const SUCCESS_PAGE_PATH = 'checkout/onepage/success';

    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;
    /**
     * @var CookieMetadataFactory
     */
    protected CookieMetadataFactory $cookieMetadataFactory;
    /**
     * @var SessionManagerInterface
     */
    protected SessionManagerInterface $sessionManager;
    /**
     * @var Session
     */
    protected Session $checkoutSession;
    /**
     * @var Data
     */
    protected Data $helper;
    /**
     * @var mixed
     */
    private $orderId;

    public function __construct(
        Data $helper,
        Session $checkoutSession,
        Context $context,
        OrderRepositoryInterface $orderRepository,
        CookieMetadataFactory $cookieMetadataFactory,
        SessionManagerInterface $sessionManager
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
        $this->sessionManager = $sessionManager;
        $this->checkoutSession = $checkoutSession;
        $this->helper = $helper;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->helper->isIframeActive();

            $cryptedString = $this->getRequest()->getParam('crypted_string', null);
            if (!$cryptedString) {
                throw new CryptedStringException();
            }

            $this->orderId = $this->helper->getCurrentOKey();
            $shopLogin = $this->helper->getMerchantId();
            $apiKey = $this->helper->getApiKey();
            $wsdl = $this->helper->getUrlWsdl();

            $client = new \SoapClient($wsdl, ['exceptions' => true]);

            $params = [
                'shopLogin' => $shopLogin,
                'CryptedString' => $cryptedString,
                'apikey' => $apiKey
            ];

            $response = $client->Decrypt($params);
            $result = simplexml_load_string($response->DecryptResult->any);
            $errCode = (string) $result->ErrorCode;
            $errDesc = (string) $result->ErrorDescription;

            // Don't force triple check (===), never trust
            if ($errCode != '0') {
                throw new \Exception($errDesc);
            }
        } catch (\SoapFault $exception) {
            return $this->helper->redirectWithError($exception->faultstring);
        } catch (\Exception $exception) {
            return $this->helper->redirectWithError($exception->getMessage());
        }

        $this->helper->cleanCookie();
        return $this->redirectToSuccessPage();
    }

    /**
     * @return \Magento\Framework\Controller\ResultInterface
     * @throws InvalidOrderIdException
     */
    protected function redirectToSuccessPage()
    {
        $result = $this->_setCheckoutSessionId();
        if (!$result) {
            throw new InvalidOrderIdException();
        }

        return $this->helper->redirect(self::SUCCESS_PAGE_PATH);
    }

    /**
     * @return bool
     */
    private function _setCheckoutSessionId()
    {
        try {
            $order = $this->orderRepository->get($this->orderId);
        } catch (\Exception $exception) {
            return false;
        }

        if (!$this->checkoutSession->getLastSuccessQuoteId()) {
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
        }

        if (!$this->checkoutSession->getLastQuoteId()) {
            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
        }

        if (!$this->checkoutSession->getLastOrderId()) {
            $this->checkoutSession->setLastOrderId($order->getEntityId());
        }

        if (!$this->checkoutSession->getLastRealOrderId()) {
            $this->checkoutSession->setLastRealOrderId($order->getEntityId());
        }

        return true;
    }
}
