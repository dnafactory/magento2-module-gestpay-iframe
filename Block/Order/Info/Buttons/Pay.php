<?php

namespace DNAFactory\BancaSellaProIframe\Block\Order\Info\Buttons;

use DNAFactory\BancaSellaProIframe\Helper\Data;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;

class Pay extends Template
{
    protected $_template = 'DNAFactory_BancaSellaProIframe::order/info/iframe_pay_button.phtml';

    /**
     * @var Data
     */
    protected Data $helper;
    /**
     * @var OrderRepositoryInterface
     */
    protected OrderRepositoryInterface $orderRepository;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Data $helper,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->helper = $helper;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @return string|null
     */
    public function getPaymentUrl()
    {
        return $this->helper->generateUrlToIframe($this->getOrderId());
    }

    public function showButton()
    {
        try {
            $this->helper->isIframeActive();
            $order = $this->orderRepository->get($this->getOrderId());
            $this->helper->validateOrder($order);
        } catch (\Exception $exception) {
            return false;
        }

        return true;
    }

    public function getOrderId()
    {
        if (!$this->hasData('order_id')) {
            return $this->getRequest()->getParam('order_id');
        }

        return $this->getData('order_id');
    }
}
