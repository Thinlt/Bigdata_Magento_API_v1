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
 *  Edit Block
 * 
 * @category     Magestore
 * @package     Magestore_Getdata
 * @author      Magestore Developer
 */
class Magestore_Getdata_Block_Adminhtml__Edit extends Mage_Adminhtml_Block_Widget_Form_Container
{
    public function __construct()
    {
        parent::__construct();
        
        $this->_objectId = 'id';
        $this->_blockGroup = 'getdata';
        $this->_controller = 'adminhtml_';
        
        $this->_updateButton('save', 'label', Mage::helper('getdata')->__('Save '));
        $this->_updateButton('delete', 'label', Mage::helper('getdata')->__('Delete '));
        
        $this->_addButton('saveandcontinue', array(
            'label'        => Mage::helper('adminhtml')->__('Save And Continue Edit'),
            'onclick'    => 'saveAndContinueEdit()',
            'class'        => 'save',
        ), -100);

        $this->_formScripts[] = "
            function toggleEditor() {
                if (tinyMCE.getInstanceById('getdata_content') == null)
                    tinyMCE.execCommand('mceAddControl', false, 'getdata_content');
                else
                    tinyMCE.execCommand('mceRemoveControl', false, 'getdata_content');
            }

            function saveAndContinueEdit(){
                editForm.submit($('edit_form').action+'back/edit/');
            }
        ";
    }
    
    /**
     * get text to show in header when edit an item
     *
     * @return string
     */
    public function getHeaderText()
    {
        if (Mage::registry('_data')
            && Mage::registry('_data')->getId()
        ) {
            return Mage::helper('getdata')->__("Edit  '%s'",
                                                $this->htmlEscape(Mage::registry('_data')->getTitle())
            );
        }
        return Mage::helper('getdata')->__('Add ');
    }
}