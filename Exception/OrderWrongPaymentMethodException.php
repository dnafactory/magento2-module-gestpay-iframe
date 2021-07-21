<?php

namespace DNAFactory\BancaSellaProIframe\Exception;

class OrderWrongPaymentMethodException extends \Magento\Framework\Exception\LocalizedException
{
    public function __construct(
        \Exception $cause = null,
        $code = 0
    ) {
        $phrase = __('This order has a not valid payment method.');
        parent::__construct($phrase, $cause, $code);
    }
}
