<?php

namespace DNAFactory\BancaSellaProIframe\Exception;

class InvalidOrderIdException extends \Magento\Framework\Exception\LocalizedException
{
    public function __construct(
        \Exception $cause = null,
        $code = 0
    ) {
        $phrase = __('Invalid Order Id, please retry.');
        parent::__construct($phrase, $cause, $code);
    }
}
