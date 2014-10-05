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
 * Product Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Getdata
 * @author      Magestore Developer
 */
class Magestore_Getdata_Adminhtml_ProductController extends Mage_Adminhtml_Controller_Action
{
    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Getdata_Adminhtml_ProductController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('getdata/product')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Products Manager'),
                Mage::helper('adminhtml')->__('Product Manager')
            );
        return $this;
    }
 
    /**
     * index action
     */
    public function indexAction()
    {
		$this->_title($this->__('Getdata'))
			->_title($this->__('Manage Products'));
        $this->_initAction()
            ->renderLayout();
    }
	
	/**
     * grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('getdata/adminhtml_product_grid')->toHtml()
        );
    }

    /**
     * view and edit item action
     */
    public function editAction()
    {
        $productId     = $this->getRequest()->getParam('id');
        $product  = Mage::getModel('getdata/product')->load($productId);

        if ($product->getId() || $productId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $product->setData($data);
            }
            Mage::register('product_data', $product);

            $this->loadLayout();
            $this->_setActiveMenu('getdata/product');

            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Product Manager'),
                Mage::helper('adminhtml')->__('Product Manager')
            );
            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Product News'),
                Mage::helper('adminhtml')->__('Product News')
            );
			
			// set title in admin
			$this->_title($this->__('Getdata'))
				->_title($this->__('Manage Products'));
			
			if($productId == 0)
				$this->_title($this->__('New Product'));
			else
				$this->_title($product->getData('title'));
			//end 
			
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('getdata/adminhtml_product_edit'))
                ->_addLeft($this->getLayout()->createBlock('getdata/adminhtml_product_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('getdata')->__('Product does not exist')
            );
            $this->_redirect('*/*/');
        }
    }
 
    public function newAction()
    {
        $this->_forward('edit');
    }
 
    /**
     * save item action
     */
    public function saveAction()
    {
        if ($data = $this->getRequest()->getPost()) {
            if (isset($_FILES['filename']['name']) && $_FILES['filename']['name'] != '') {
                try {
                    /* Starting upload */    
                    $uploader = new Varien_File_Uploader('filename');
                    
                    // Any extention would work
                       $uploader->setAllowedExtensions(array('jpg','jpeg','gif','png'));
                    $uploader->setAllowRenameFiles(false);
                    
                    // Set the file upload mode 
                    // false -> get the file directly in the specified folder
                    // true -> get the file in the product like folders 
                    //    (file.jpg will go in something like /media/f/i/file.jpg)
                    $uploader->setFilesDispersion(false);
                            
                    // We set media as the upload dir
                    $path = Mage::getBaseDir('media') . DS ;
                    $result = $uploader->save($path, $_FILES['filename']['name'] );
                    $data['filename'] = $result['file'];
                } catch (Exception $e) {
                    $data['filename'] = $_FILES['filename']['name'];
                }
            }
              
            $product = Mage::getModel('getdata/product');        
            $product->setData($data)
                ->setId($this->getRequest()->getParam('id'));
            
            try {
                if ($product->getCreatedTime == NULL || $product->getUpdateTime() == NULL) {
                    $product->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $product->setUpdateTime(now());
                }
                $product->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('getdata')->__('Product was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $product->getId()));
                    return;
                }
                $this->_redirect('*/*/');
                return;
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                Mage::getSingleton('adminhtml/session')->setFormData($data);
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
                return;
            }
        }
        Mage::getSingleton('adminhtml/session')->addError(
            Mage::helper('getdata')->__('Unable to find product to save')
        );
        $this->_redirect('*/*/');
    }
 
    /**
     * delete item action
     */
    public function deleteAction()
    {
        if ($this->getRequest()->getParam('id') > 0) {
            try {
                $product = Mage::getModel('getdata/product');
                $product->setId($this->getRequest()->getParam('id'))
                    ->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Product was successfully deleted')
                );
                $this->_redirect('*/*/');
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
                $this->_redirect('*/*/edit', array('id' => $this->getRequest()->getParam('id')));
            }
        }
        $this->_redirect('*/*/');
    }

    /**
     * mass delete item(s) action
     */
    public function massDeleteAction()
    {
        $productIds = $this->getRequest()->getParam('product');
        if (!is_array($productIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select product(s)'));
        } else {
            try {
                foreach ($productIds as $productId) {
                    $product = Mage::getModel('getdata/product')->load($productId);
                    $product->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted',
                    count($productIds))
                );
            } catch (Exception $e) {
                Mage::getSingleton('adminhtml/session')->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }
    
    /**
     * mass change status for item(s) action
     */
    public function massStatusAction()
    {
        $productIds = $this->getRequest()->getParam('product');
        if (!is_array($productIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select product(s)'));
        } else {
            try {
                foreach ($productIds as $productId) {
                    Mage::getSingleton('getdata/product')
                        ->load($productId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($productIds))
                );
            } catch (Exception $e) {
                $this->_getSession()->addError($e->getMessage());
            }
        }
        $this->_redirect('*/*/index');
    }

    /**
     * export grid item to CSV type
     */
    public function exportCsvAction()
    {
        $fileName   = 'product.csv';
        $content    = $this->getLayout()
                           ->createBlock('getdata/adminhtml_product_grid')
                           ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction()
    {
        $fileName   = 'product.xml';
        $content    = $this->getLayout()
                           ->createBlock('getdata/adminhtml_product_grid')
                           ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('product');
    }
}