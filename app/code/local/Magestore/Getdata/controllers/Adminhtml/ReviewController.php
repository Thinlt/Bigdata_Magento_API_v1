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
 * Review Adminhtml Controller
 * 
 * @category    Magestore
 * @package     Magestore_Getdata
 * @author      Magestore Developer
 */
class Magestore_Getdata_Adminhtml_ReviewController extends Mage_Adminhtml_Controller_Action
{
    /**
     * init layout and set active for current menu
     *
     * @return Magestore_Getdata_Adminhtml_ReviewController
     */
    protected function _initAction()
    {
        $this->loadLayout()
            ->_setActiveMenu('getdata/review')
            ->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Reviews Manager'),
                Mage::helper('adminhtml')->__('Review Manager')
            );
        return $this;
    }
 
    /**
     * index action
     */
    public function indexAction()
    {
		$this->_title($this->__('Getdata'))
			->_title($this->__('Manage Reviews'));
        $this->_initAction()
            ->renderLayout();
    }
	
	/**
     * grid for AJAX request
     */
    public function gridAction()
    {
        $this->getResponse()->setBody(
            $this->getLayout()->createBlock('getdata/adminhtml_review_grid')->toHtml()
        );
    }

    /**
     * view and edit item action
     */
    public function editAction()
    {
        $reviewId     = $this->getRequest()->getParam('id');
        $review  = Mage::getModel('getdata/review')->load($reviewId);

        if ($review->getId() || $reviewId == 0) {
            $data = Mage::getSingleton('adminhtml/session')->getFormData(true);
            if (!empty($data)) {
                $review->setData($data);
            }
            Mage::register('review_data', $review);

            $this->loadLayout();
            $this->_setActiveMenu('getdata/review');

            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Review Manager'),
                Mage::helper('adminhtml')->__('Review Manager')
            );
            $this->_addBreadcrumb(
                Mage::helper('adminhtml')->__('Review News'),
                Mage::helper('adminhtml')->__('Review News')
            );
			
			// set title in admin
			$this->_title($this->__('Getdata'))
				->_title($this->__('Manage Reviews'));
			
			if($reviewId == 0)
				$this->_title($this->__('New Review'));
			else
				$this->_title($review->getData('title'));
			//end 
			
            $this->getLayout()->getBlock('head')->setCanLoadExtJs(true);
            $this->_addContent($this->getLayout()->createBlock('getdata/adminhtml_review_edit'))
                ->_addLeft($this->getLayout()->createBlock('getdata/adminhtml_review_edit_tabs'));

            $this->renderLayout();
        } else {
            Mage::getSingleton('adminhtml/session')->addError(
                Mage::helper('getdata')->__('Review does not exist')
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
              
            $review = Mage::getModel('getdata/review');        
            $review->setData($data)
                ->setId($this->getRequest()->getParam('id'));
            
            try {
                if ($review->getCreatedTime == NULL || $review->getUpdateTime() == NULL) {
                    $review->setCreatedTime(now())
                        ->setUpdateTime(now());
                } else {
                    $review->setUpdateTime(now());
                }
                $review->save();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('getdata')->__('Review was successfully saved')
                );
                Mage::getSingleton('adminhtml/session')->setFormData(false);

                if ($this->getRequest()->getParam('back')) {
                    $this->_redirect('*/*/edit', array('id' => $review->getId()));
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
            Mage::helper('getdata')->__('Unable to find review to save')
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
                $review = Mage::getModel('getdata/review');
                $review->setId($this->getRequest()->getParam('id'))
                    ->delete();
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Review was successfully deleted')
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
        $reviewIds = $this->getRequest()->getParam('review');
        if (!is_array($reviewIds)) {
            Mage::getSingleton('adminhtml/session')->addError(Mage::helper('adminhtml')->__('Please select review(s)'));
        } else {
            try {
                foreach ($reviewIds as $reviewId) {
                    $review = Mage::getModel('getdata/review')->load($reviewId);
                    $review->delete();
                }
                Mage::getSingleton('adminhtml/session')->addSuccess(
                    Mage::helper('adminhtml')->__('Total of %d record(s) were successfully deleted',
                    count($reviewIds))
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
        $reviewIds = $this->getRequest()->getParam('review');
        if (!is_array($reviewIds)) {
            Mage::getSingleton('adminhtml/session')->addError($this->__('Please select review(s)'));
        } else {
            try {
                foreach ($reviewIds as $reviewId) {
                    Mage::getSingleton('getdata/review')
                        ->load($reviewId)
                        ->setStatus($this->getRequest()->getParam('status'))
                        ->setIsMassupdate(true)
                        ->save();
                }
                $this->_getSession()->addSuccess(
                    $this->__('Total of %d record(s) were successfully updated', count($reviewIds))
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
        $fileName   = 'review.csv';
        $content    = $this->getLayout()
                           ->createBlock('getdata/adminhtml_review_grid')
                           ->getCsv();
        $this->_prepareDownloadResponse($fileName, $content);
    }

    /**
     * export grid item to XML type
     */
    public function exportXmlAction()
    {
        $fileName   = 'review.xml';
        $content    = $this->getLayout()
                           ->createBlock('getdata/adminhtml_review_grid')
                           ->getXml();
        $this->_prepareDownloadResponse($fileName, $content);
    }
    
    protected function _isAllowed()
    {
        return Mage::getSingleton('admin/session')->isAllowed('review');
    }
}