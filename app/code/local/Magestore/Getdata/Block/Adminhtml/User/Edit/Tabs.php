<?php
/**
 * Magestore
 * 
 * NOTICE OF LICENSE
 * 
 * This source file is subject to the Magestore.com license that is
 * available through the world-wide-web at this URL:
 * http://www.magestore.com/license-agreement.html
 * 
 * DISCLAIMER
 * 
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 * 
 * @category    Magestore
 * @package     Magestore_Getdata
 * @copyright   Copyright (c) 2012 Magestore (http://www.magestore.com/)
 * @license     http://www.magestore.com/license-agreement.html
 */

/**
 * User Edit Tabs Block
 * 
 * @category    Magestore
 * @package     Magestore_Getdata
 * @author      Magestore Developer
 */
class Magestore_Getdata_Block_Adminhtml_User_Edit_Tabs extends Mage_Adminhtml_Block_Widget_Tabs
{
    public function __construct()
    {
        parent::__construct();
        $this->setId('user_tabs');
        $this->setDestElementId('edit_form');
        $this->setTitle(Mage::helper('getdata')->__('User Information'));
    }
    
    /**
     * prepare before render block to html
     *
     * @return Magestore_Getdata_Block_Adminhtml_User_Edit_Tabs
     */
    protected function _beforeToHtml()
    {
        $this->addTab('form_section', array(
            'label'     => Mage::helper('getdata')->__('User Information'),
            'title'     => Mage::helper('getdata')->__('User Information'),
            'content'   => $this->getLayout()
                                ->createBlock('getdata/adminhtml_user_edit_tab_form')
                                ->toHtml(),
        ));
        return parent::_beforeToHtml();
    }
}