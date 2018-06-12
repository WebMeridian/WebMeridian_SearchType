<?php

/**
 * Copyright © 2013-2017 Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */
namespace WebMeridian\SearchType\Adapter\Mysql\Query\Builder;

use Magento\Framework\DB\Helper\Mysql\Fulltext;
use Magento\Framework\DB\Select;
use Magento\Framework\Search\Adapter\Mysql\Field\FieldInterface;
use Magento\Framework\Search\Adapter\Mysql\Field\ResolverInterface;
use Magento\Framework\Search\Adapter\Mysql\ScoreBuilder;
use Magento\Framework\Search\Request\Query\BoolExpression;
use Magento\Framework\Search\Request\QueryInterface as RequestQueryInterface;
use Magento\Framework\Search\Adapter\Preprocessor\PreprocessorInterface;

class Match extends \Magento\Framework\Search\Adapter\Mysql\Query\Builder\Match
{
    const SPECIAL_CHARACTERS = '-+~/\\<>\'":*$#@()!,.?`=%&^';

    const MINIMAL_CHARACTER_LENGTH = 3;

    /**
     * @var string[]
     */
    private $replaceSymbols = [];

    /**
     * @var ResolverInterface
     */
    private $resolver;

    /**
     * @var Fulltext
     */
    private $fulltextHelper;

    /**
     * @var string
     */
    private $fulltextSearchMode;

    /**
     * Catalog resource helper
     *
     * @var \Magento\Catalog\Model\ResourceModel\Helper
     */
    protected $_resourceHelper;


    /**
     * @var \WebMeridian\SearchType\Helper\Config
     */
    protected $_hlpConfig;

    /**
     * @var PreprocessorInterface[]
     */
    protected $preprocessors;
    public function __construct(
        ResolverInterface $resolver,
        Fulltext $fulltextHelper,
        \Magento\Catalog\Model\ResourceModel\Helper $resourceHelper,
        \WebMeridian\SearchType\Helper\Config $hlpConfig,
        $fulltextSearchMode = Fulltext::FULLTEXT_MODE_BOOLEAN,
        array $preprocessors = []
    ) {
        $this->resolver = $resolver;
        $this->replaceSymbols = str_split(self::SPECIAL_CHARACTERS, 1);
        $this->fulltextHelper = $fulltextHelper;
        $this->fulltextSearchMode = $fulltextSearchMode;
        $this->preprocessors = $preprocessors;
        $this->_resourceHelper = $resourceHelper;
        $this->_hlpConfig = $hlpConfig;
        parent::__construct($resolver, $fulltextHelper,$fulltextSearchMode, $preprocessors);
    }

    /**
     * {@inheritdoc}
     */
    public function build(
        ScoreBuilder $scoreBuilder,
        Select $select,
        RequestQueryInterface $query,
        $conditionType
    ) {

        /** @var $query \Magento\Framework\Search\Request\Query\Match */
        $queryValue = $this->prepareQuery($query->getValue(), $conditionType);

        $fieldList = [];
        foreach ($query->getMatches() as $match) {
            $fieldList[] = $match['field'];
        }
        $resolvedFieldList = $this->resolver->resolve($fieldList);

        $fieldIds = [];
        $columns = [];
        foreach ($resolvedFieldList as $field) {
            if ($field->getType() === FieldInterface::TYPE_FULLTEXT && $field->getAttributeId()) {
                $fieldIds[] = $field->getAttributeId();
            }
            $column = $field->getColumn();
            $columns[$column] = $column;
        }

        if($this->isLikeStrategy()){
            $matchQuery = $this->buildLikeQuery($query->getValue());
        }else{
            $matchQuery = $this->fulltextHelper->getMatchQuery(
                $columns,
                $queryValue,
                $this->fulltextSearchMode
            );
        }


        $scoreBuilder->addCondition($matchQuery, true);

        if ($fieldIds) {
            $matchQuery = sprintf('(%s AND search_index.attribute_id IN (%s))', $matchQuery, implode(',', $fieldIds));
        }

        $select->where($matchQuery);

        return $select;
    }

    public function isLikeStrategy()
    {
        $searchType = $this->_hlpConfig->getConfigData('catalog/search/search_type');
        if($searchType == 'like') return true;
        return false;
    }

    public function buildLikeQuery($queryValue)
    {
        $maxQueryWord = $this->_hlpConfig->getConfigData('catalog/search/max_query_words');
        $filter = new \Magento\Framework\Filter\SplitWords(false, $maxQueryWord);
        $words = $filter->filter($queryValue);
        $like = array();
        $likeCond = '';
        $like[] = $this->_resourceHelper->getCILike('data_index', implode(' ', $words), array('position' => 'any'));

        if ($like) {
            $likeCond = '(' . join(' AND ', $like) . ')';
        }

        return $likeCond;
    }
}
