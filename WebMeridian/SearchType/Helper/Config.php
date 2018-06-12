<?php
namespace WebMeridian\SearchType\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\App\Helper\Context;

class Config extends AbstractHelper
{

    public function __construct(Context $context)
    {
        $this->scopeConfig = $context->getScopeConfig();
        parent::__construct($context);
    }

    /**
     * Store Config value
     *
     * @param string $key Config value key
     * @return string
     */
    public function getConfigData($key)
    {
        $value = $this->scopeConfig->getValue(
            $key,
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        return $value;
    }
}