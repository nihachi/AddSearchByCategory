<?php

namespace Fullstack\Custom\Model;

use Magento\Catalog\Model\Config;
use Magento\Catalog\Model\Product\Visibility;
use Magento\Catalog\Model\ProductFactory;
use Magento\Catalog\Model\ResourceModel\Product\Attribute\CollectionFactory as AttributeCollectionFactory;
use Magento\Catalog\Model\ResourceModel\Product\CollectionFactory as ProductCollectionFactory;
use Magento\CatalogSearch\Model\ResourceModel\Advanced\Collection as ProductCollection;
use Magento\CatalogSearch\Model\ResourceModel\AdvancedFactory;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Eav\Model\Entity\Attribute as EntityAttribute;
use Magento\Framework\Model\Context;
use Magento\Framework\Registry;
use Magento\Store\Model\StoreManagerInterface;

class ExtendAdvanced extends \Magento\CatalogSearch\Model\Advanced
{
    /**
     * @var \Magento\Framework\ObjectManagerInterface
     */
    protected $_objectManager;

    /**
     * @param Context $context
     * @param Registry $registry
     * @param AttributeCollectionFactory $attributeCollectionFactory
     * @param Visibility $catalogProductVisibility
     * @param Config $catalogConfig
     * @param CurrencyFactory $currencyFactory
     * @param ProductFactory $productFactory
     * @param StoreManagerInterface $storeManager
     * @param ProductCollectionFactory $productCollectionFactory
     * @param AdvancedFactory $advancedFactory
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param array $data
     */
    public function __construct(
        Context $context,
        Registry $registry,
        AttributeCollectionFactory $attributeCollectionFactory,
        Visibility $catalogProductVisibility,
        Config $catalogConfig,
        CurrencyFactory $currencyFactory,
        ProductFactory $productFactory,
        StoreManagerInterface $storeManager,
        ProductCollectionFactory $productCollectionFactory,
        AdvancedFactory $advancedFactory,
        \Magento\Framework\ObjectManagerInterface $objectManager,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $registry,
            $attributeCollectionFactory,
            $catalogProductVisibility,
            $catalogConfig,
            $currencyFactory,
            $productFactory,
            $storeManager,
            $productCollectionFactory,
            $advancedFactory,
            $data
        );
        $this->_objectManager = $objectManager;
    }

    // Here is you custom method from question


        /**
     * Add advanced search filters to product collection
     *
     * @param   array $values
     * @return  $this
     * @throws LocalizedException
     * @SuppressWarnings(PHPMD.CyclomaticComplexity)
     * @SuppressWarnings(PHPMD.NPathComplexity)
     */
    public function addFilters($values)
    {
        $attributes = $this->getAttributes();
        $allConditions = [];

        foreach ($attributes as $attribute) {
            /* @var $attribute Attribute */
            if (!isset($values[$attribute->getAttributeCode()])) {
                continue;
            }
            $value = $values[$attribute->getAttributeCode()];
            $preparedSearchValue = $this->getPreparedSearchCriteria($attribute, $value);
            if (false === $preparedSearchValue) {
                continue;
            }
            $this->addSearchCriteria($attribute, $preparedSearchValue);

            if ($attribute->getAttributeCode() == 'price') {
                $rate = 1;
                $store = $this->_storeManager->getStore();
                $currency = $store->getCurrentCurrencyCode();
                if ($currency != $store->getBaseCurrencyCode()) {
                    $rate = $store->getBaseCurrency()->getRate($currency);
                }

                $value['from'] = (isset($value['from']) && is_numeric($value['from']))
                    ? (float)$value['from'] / $rate
                    : '';
                $value['to'] = (isset($value['to']) && is_numeric($value['to']))
                    ? (float)$value['to'] / $rate
                    : '';
            }

            if ($attribute->getBackendType() == 'datetime') {
                $value['from'] = (isset($value['from']) && !empty($value['from']))
                    ? date('Y-m-d\TH:i:s\Z', strtotime($value['from']))
                    : '';
                $value['to'] = (isset($value['to']) && !empty($value['to']))
                    ? date('Y-m-d\TH:i:s\Z', strtotime($value['to']))
                    : '';
            }
            $condition = $this->_getResource()->prepareCondition(
                $attribute,
                $value,
                $this->getProductCollection()
            );
            if ($condition === false) {
                continue;
            }

            $table = $attribute->getBackend()->getTable();
            if ($attribute->getBackendType() == 'static') {
                $attributeId = $attribute->getAttributeCode();
            } else {
                $attributeId = $attribute->getId();
            }
            $allConditions[$table][$attributeId] = $condition;
        }
        //if ($allConditions)
        if ($allConditions || (isset($values['cat']) && is_numeric($values['cat'])) ) {
            $this->_registry->register('advanced_search_conditions', $allConditions);
            $this->getProductCollection()->addFieldsToFilter($allConditions);
        } else {
            throw new LocalizedException(__('Please specify at least one search term.'));
        }

        return $this;
    }


    public function getSearchCriterias()
    {
        $search = $this->_searchCriterias;
        /* display category filtering criteria */
        if(isset($_GET['cat']) && is_numeric($_GET['cat'])) {
            $category = $this->_objectManager->get('Magento\Catalog\Model\Category')->load($_GET['cat']);
            $search[] = array('name'=>'cat','value'=>$category->getName());
        }
        return $search;
    }

    public function getProductCollection()
    {
        if ($this->_productCollection === null) {
            $this->_productCollection = $this->_objectManager->get('Magento\CatalogSearch\Model\ResourceModel\Advanced\Collection')
                ->addAttributeToSelect($this->_objectManager->get('Magento\Catalog\Model\Config')->getProductAttributes())
                ->addMinimalPrice()
                ->addStoreFilter();
            /* need to include product active and visibility filtering here*/    
            /* include category filtering */
            if(isset($_GET['cat']) && is_numeric($_GET['cat'])) $this->_productCollection->addCategoryFilter($this->_objectManager->get('Magento\Catalog\Model\Category')->load($_GET['cat']),true);
        }

        return $this->_productCollection;
    }
}