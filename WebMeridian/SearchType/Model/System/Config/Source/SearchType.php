<?php

namespace WebMeridian\SearchType\Model\System\Config\Source;


class SearchType implements \Magento\Framework\Option\ArrayInterface
{
    /**
     * @return array
     */
    public function toOptionArray()
    {
        return [
            ['value' => 'fulltext', 'label' => __('Fulltext')],
            ['value' => 'like', 'label' => __('Like')]
        ];
    }
}