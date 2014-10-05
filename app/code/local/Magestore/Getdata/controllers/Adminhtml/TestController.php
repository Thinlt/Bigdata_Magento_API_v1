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
 * Test Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Getdata
 * @author      Magestore Developer
 */
class Magestore_Getdata_Adminhtml_TestController extends Mage_Adminhtml_Controller_Action
{
    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Getdata_Adminhtml_TestController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('getdata/test')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Tests Manager'),
                Mage::helper('adminhtml')->__('Test Manager')
            );
        return $this;
    }
 
    /**
     * index action
     */
    public function indexAction()
    {
		$this->_title($this->__('Getdata'))
			->_title($this->__('Manage Tests'));
        $this->_initAction()
            ->renderLayout();
    }
	
	/**
     * grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('getdata/adminhtml_test_grid')->toHtml()
        );
    }

    /**
     * view and edit item action
     */
    public function editAction()
    {
        $testId     = $this->getRequest()->getParam('id');
        $test  = Mage::getModel('getdata/test')->load($testId);

        if ($test->getId() || $testId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $test->setData($data);
            }
            Mage::register('test_data', $test);

            $this->loadLayout();
            $this->_setActiveMenu('getdata/test');

            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Test Manager'),
                Mage::helper('adminhtml')->__('Test Manager')
            );
            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Test News'),
                Mage::helper('adminhtml')->__('Test News')
            );
			
			// set title in admin
			$this->_title($this->__('Getdata'))
				->_title($this->__('Manage Tests'));
			
			if($testId == 0)
				$this->_title($this->__('New Test'));
			else
				$this->_title($test->getData('title'));
			//end 
			
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('getdata/adminhtml_test_edit'))
                ->_addLeft($this->getLayout()->createBlock('getdata/adminhtml_test_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('getdata')->__('Test does not exist')
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
              
            $test = Mage::getModel('getdata/test');        
            $test->setData($data)
                ->setId($this->getRequest()->getParam('id'));
            
            try {
                if ($test->getCreatedTime == NULL || $test->getUpdateTime() == NULL) {
                    $test->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $test->setUpdateTime(now());
                }
                $test->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('getdata')->__('Test was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $test->getId()));
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
            Mage::helper('getdata')->__('Unable to find test to save')
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
                $test = Mage::getModel('getdata/test');
                $test->setId($this->getRequest()->getParam('id'))
                    ->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Test was successfully deleted')
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
        $testIds = $this->getRequest()->getParam('test');
        if (!is_array($testIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select test(s)'));
        } else {
            try {
                foreach ($testIds as $testId) {
                    $test = Mage::getModel('getdata/test')->load($testId);
                    $test->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted',
                    count($testIds))
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
        $testIds = $this->getRequest()->getParam('test');
        if (!is_array($testIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select test(s)'));
        } else {
            try {
                foreach ($testIds as $testId) {
                    Mage::getSingleton('getdata/test')
                        ->load($testId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($testIds))
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
        $fileName   = 'test.csv';
        $content    = $this->getLayout()
                           ->createBlock('getdata/adminhtml_test_grid')
                           ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction()
    {
        $fileName   = 'test.xml';
        $content    = $this->getLayout()
                           ->createBlock('getdata/adminhtml_test_grid')
                           ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('test');
    }
}