<?php

namespace DNAFactory\BancaSellaProIframe\Plugin\Block\Adminhtml\Order;

use DNAFactory\BancaSellaProIframe\Helper\Data;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Url;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\App\Emulation;

class View
{
    /**
     * @var EncryptorInterface
     */
    protected EncryptorInterface $encryptor;
    /**
     * @var Url
     */
    protected Url $urlBuilder;
    /**
     * @var RequestInterface
     */
    protected RequestInterface $request;
    /**
     * @var Emulation
     */
    protected Emulation $emulation;
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;
    /**
     * @var Data
     */
    protected Data $helper;

    public function __construct(
        Data $helper,
        OrderRepositoryInterface $orderRepository,
        Emulation $emulation,
        EncryptorInterface $encryptor,
        Url $urlBuilder,
        RequestInterface $request
    ) {
        $this->encryptor = $encryptor;
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->emulation = $emulation;
        $this->orderRepository = $orderRepository;
        $this->helper = $helper;
    }

    public function beforeSetLayout(
        \Magento\Sales\Block\Adminhtml\Order\View $view
    ) {
        try {
            $this->helper->isIframeActive();

            $orderId = $this->request->getParam('order_id');
            $order = $this->_getOrder($orderId);
            $this->helper->validateOrder($order);
        } catch (\Exception $exception) {
            return;
        }

        $url = $this->helper->generateUrlToIframe($orderId);

        $view->addButton(
            'axerve_iframe',
            [
                'label' => __('Axerve Iframe'),
                'class' => 'primary',
                'onclick' => "window.open('$url', '_blank')"
            ]
        );
    }

    private function _getOrder($orderId)
    {
        return $this->orderRepository->get($orderId);
    }
}
