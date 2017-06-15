<?php
/**
 * Copyright © 2016 Magento. All rights reserved.
 * See COPYING.txt for license details.
 */

/**
 * Advanced search form
 *
 * @author      Magento Core Team <core@magentocommerce.com>
 */
namespace Fullstack\Custom\Block\Advanced;

use Magento\CatalogSearch\Model\Advanced;
use Magento\Directory\Model\CurrencyFactory;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Framework\Data\Collection\AbstractDb as DbCollection;
use Magento\Framework\View\Element\AbstractBlock;
use Magento\Framework\View\Element\BlockInterface;
use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;

class ExtendForm extends \Magento\CatalogSearch\Block\Advanced\Form
{

    protected $_categoryHelper;

    public function __construct(
        \Magento\Catalog\Helper\Category $categoryHelper
    ){
        $this->_categoryHelper = $categoryHelper;
    }


    public function getStoreCategories()
    {
       return $this->_categoryHelper->getStoreCategories();
    } 
}
