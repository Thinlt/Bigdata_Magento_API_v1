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
 * Viewproduct Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Getdata
 * @author      Magestore Developer
 */
class Magestore_Getdata_Adminhtml_ViewproductController extends Mage_Adminhtml_Controller_Action
{
    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Getdata_Adminhtml_ViewproductController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('getdata/viewproduct')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Viewproducts Manager'),
                Mage::helper('adminhtml')->__('Viewproduct Manager')
            );
        return $this;
    }
 
    /**
     * index action
     */
    public function indexAction()
    {
		$this->_title($this->__('Getdata'))
			->_title($this->__('Manage Viewproducts'));
        $this->_initAction()
            ->renderLayout();
    }
	
	/**
     * grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('getdata/adminhtml_viewproduct_grid')->toHtml()
        );
    }

    /**
     * view and edit item action
     */
    public function editAction()
    {
        $viewproductId     = $this->getRequest()->getParam('id');
        $viewproduct  = Mage::getModel('getdata/viewproduct')->load($viewproductId);

        if ($viewproduct->getId() || $viewproductId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $viewproduct->setData($data);
            }
            Mage::register('viewproduct_data', $viewproduct);

            $this->loadLayout();
            $this->_setActiveMenu('getdata/viewproduct');

            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Viewproduct Manager'),
                Mage::helper('adminhtml')->__('Viewproduct Manager')
            );
            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Viewproduct News'),
                Mage::helper('adminhtml')->__('Viewproduct News')
            );
			
			// set title in admin
			$this->_title($this->__('Getdata'))
				->_title($this->__('Manage Viewproducts'));
			
			if($viewproductId == 0)
				$this->_title($this->__('New Viewproduct'));
			else
				$this->_title($viewproduct->getData('title'));
			//end 
			
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('getdata/adminhtml_viewproduct_edit'))
                ->_addLeft($this->getLayout()->createBlock('getdata/adminhtml_viewproduct_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('getdata')->__('Viewproduct does not exist')
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
              
            $viewproduct = Mage::getModel('getdata/viewproduct');        
            $viewproduct->setData($data)
                ->setId($this->getRequest()->getParam('id'));
            
            try {
                if ($viewproduct->getCreatedTime == NULL || $viewproduct->getUpdateTime() == NULL) {
                    $viewproduct->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $viewproduct->setUpdateTime(now());
                }
                $viewproduct->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('getdata')->__('Viewproduct was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $viewproduct->getId()));
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
            Mage::helper('getdata')->__('Unable to find viewproduct to save')
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
                $viewproduct = Mage::getModel('getdata/viewproduct');
                $viewproduct->setId($this->getRequest()->getParam('id'))
                    ->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Viewproduct was successfully deleted')
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
        $viewproductIds = $this->getRequest()->getParam('viewproduct');
        if (!is_array($viewproductIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select viewproduct(s)'));
        } else {
            try {
                foreach ($viewproductIds as $viewproductId) {
                    $viewproduct = Mage::getModel('getdata/viewproduct')->load($viewproductId);
                    $viewproduct->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted',
                    count($viewproductIds))
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
        $viewproductIds = $this->getRequest()->getParam('viewproduct');
        if (!is_array($viewproductIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select viewproduct(s)'));
        } else {
            try {
                foreach ($viewproductIds as $viewproductId) {
                    Mage::getSingleton('getdata/viewproduct')
                        ->load($viewproductId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($viewproductIds))
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
        $fileName   = 'viewproduct.csv';
        $content    = $this->getLayout()
                           ->createBlock('getdata/adminhtml_viewproduct_grid')
                           ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction()
    {
        $fileName   = 'viewproduct.xml';
        $content    = $this->getLayout()
                           ->createBlock('getdata/adminhtml_viewproduct_grid')
                           ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('viewproduct');
    }
}