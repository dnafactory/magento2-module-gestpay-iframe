<?php

namespace DNAFactory\BancaSellaProIframe\Exception;

class OrderAlreadyPaidException extends \Magento\Framework\Exception\LocalizedException
{
    public function __construct(
        \Exception $cause = null,
        $code = 0
    ) {
        $phrase = __('This order is already paid.');
        parent::__construct($phrase, $cause, $code);
    }
}
