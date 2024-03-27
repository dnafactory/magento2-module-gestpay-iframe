<?php

namespace DNAFactory\BancaSellaProIframe\Controller\Iframe;

use DNAFactory\BancaSellaProIframe\Exception\InvalidOrderIdException;
use DNAFactory\BancaSellaProIframe\Helper\Data;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\Session\SessionManagerInterface;
use Magento\Framework\Stdlib\Cookie\CookieMetadataFactory;
use Magento\Sales\Api\OrderRepositoryInterface;

class Index extends Action implements HttpGetActionInterface, HttpPostActionInterface
{
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;
    /**
     * @var SessionManagerInterface
     */
    protected SessionManagerInterface $sessionManager;
    /**
     * @var Data
     */
    protected Data $helper;
    /**
     * @var CookieMetadataFactory
     */
    protected CookieMetadataFactory $cookieMetadataFactory;
    /**
     * @var mixed
     */
    private $orderId;

    public function __construct(
        CookieMetadataFactory $cookieMetadataFactory,
        Data $helper,
        Context $context,
        OrderRepositoryInterface $orderRepository,
        SessionManagerInterface $sessionManager
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->sessionManager = $sessionManager;
        $this->helper = $helper;
        $this->cookieMetadataFactory = $cookieMetadataFactory;
    }

    /**
     * @inheritdoc
     */
    public function execute()
    {
        try {
            $this->helper->isIframeActive();

            $pares = $this->getRequest()->getParam('PaRes', '') ?? '';

            if (strlen($pares) > 0) {
                return $this->helper->redirectToPayment();
            } else {
                $this->helper->cleanCookie();
            }

            $this->orderId = $this->helper->getCurrentOKey();
            if (!$this->orderId) {
                throw new InvalidOrderIdException();
            }

            $order = $this->orderRepository->get($this->orderId);
            if (!$order || !$order->getEntityId()) {
                throw new InvalidOrderIdException();
            }

            $this->helper->validateOrder($order);

            $shopLogin = $this->helper->getMerchantId();
            $wsdl = $this->helper->getUrlWsdl();
            $shopTransactionId = $order->getIncrementId();
            $apiKey = $this->helper->getApiKey();
            ///////////////////////
            /// PZ8
            /// baseGrandTotal refer to base currency
            /// workaround to avoid currency mapping
            ///
            $amount = $order->getBaseGrandTotal();
            $amount = number_format($amount, 2, '.', '');
            $currency = $this->helper->getCurrency();
            ///
            ///////////////////////

            $client = new \SoapClient($wsdl, ['exceptions' => true]);

            $params = [
                'shopLogin' => $shopLogin,
                'uicCode' => $currency,
                'amount' => $amount,
                'shopTransactionId' => $shopTransactionId,
                'apikey' => $apiKey
            ];

            $response = $client->Encrypt($params);
            $result = simplexml_load_string($response->EncryptResult->any);
            $errCode = (string)$result->ErrorCode;
            $errDesc = (string)$result->ErrorDescription;

            // Don't force triple check (===), never trust
            if ($errCode != '0') {
                throw new \Exception($errDesc);
            }
        } catch (\SoapFault $exception) {
            return $this->helper->redirectWithError($exception->faultstring);
        } catch (\Exception $exception) {
            return $this->helper->redirectWithError($exception->getMessage());
        }

        $encString = (string)$result->CryptDecryptString;

        $metadata = $this->cookieMetadataFactory
            ->createPublicCookieMetadata()
            ->setDuration(86400)
            ->setPath($this->sessionManager->getCookiePath())
            ->setDomain($this->sessionManager->getCookieDomain());

        $this->helper->getCookieManager()->setPublicCookie(
            'encString',
            $encString,
            $metadata
        );

        return $this->helper->redirectToPayment([
            'o_key' => $this->helper->encrypt($this->orderId)
        ]);
    }
}
