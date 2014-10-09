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
 * Getdata Observer Model
 *
 * @category    Magestore
 * @package     Magestore_Getdata
 * @author      Magestore Developer
 */
class Magestore_Getdata_Model_Observer
{
    /**
     * process controller_action_predispatch event
     *
     * @return Magestore_Getdata_Model_Observer
     */
    public function controllerActionPredispatch($observer)
    {
        $action = $observer->getEvent()->getControllerAction();
		$sessionId = Mage::app()->getCookie()->get('frontend');
		if($sessionId && !Mage::getSingleton('core/session')->getData('getdata_session_id'))
			Mage::getSingleton('core/session')->setData('getdata_session_id', $sessionId);
        return $this;
    }

	 public function controllerActionPostdispatch($observer)
    {
        $action = $observer->getEvent()->getControllerAction();
		$oldSessionId = Mage::getSingleton('core/session')->getData('getdata_session_id');

		$visitor = Mage::getSingleton('core/session')->getData('getdata_visitor');
		if(!$visitor || !$visitor->getId()){
			$visitor = Mage::getModel('getdata/visitor')->load($oldSessionId, 'session_id');
			$customer = Mage::getSingleton('customer/session')->getCustomer();
			if($customer && $customer->getId())
				$visitor->setCustomerId($customer->getId());

			Mage::getSingleton('core/session')->setData('getdata_visitor', $visitor);
		}

		$helper = Mage::helper('core/http');


		//log visitor
		if($visitor && $visitor->getId()){
			$newIp = $helper->getRemoteAddr();
			$ips = explode(',', $visitor->getRemoteAddr());
			if(!in_array($newIp, $ips))
				$ips[] = $newIp;

			$visitor->setLastVisitTime(now())
					->setRemoteAddr(implode(',', $ips));
		}else{
			$visitor->setSessionId($oldSessionId)
					->setFirstVisitTime(now())
					->setLastVisitTime(now())
					->setHttpUserAgent($helper->getHttpUserAgent(true))
					->setRemoteAddr($helper->getRemoteAddr());
		}

		try{
			$visitor->save();
		}catch(Exception $e){
		}

		$referer = $helper->getHttpReferer();
		$baseUrl = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_WEB);
		if($referer && $referer != $baseUrl && $referer != trim($baseUrl, '/') &&
				$referer != $baseUrl . 'index.php' && $referer != $baseUrl . 'index.php/'){
			$requestPath = str_replace(array($baseUrl, 'index.php/'), '', $referer);
			$store = Mage::app()->getStore();
			$urlRewrite = Mage::getModel('core/url_rewrite')
					->setStoreId($store->getId())
					->loadByRequestPath($requestPath);
			$previousProductId = $urlRewrite->getProductId();
		}
		//log product view
		$request = Mage::app()->getRequest();
		$controller = $request->getControllerName(); // return controller name
		$action = $request->getActionName(); // return action name
		$route = $request->getRouteName(); // return routes name
		if($route == 'catalog' && $controller == 'product' && $action == 'view'){
			$product = Mage::registry('current_product');
			$referer = $helper->getHttpReferer();

			$model = Mage::getModel('getdata/viewproduct')
					->setProductId($product->getId())
					->setVisitorId($visitor->getVisitorId())
					->setUserId($visitor->getCustomerId())
					->setStartViewedTime(now())
					->setPreviousProductId($previousProductId);
			try{
				$model->save();
			}catch(Exception $e){

			}
		}

    }

	public function addItem($observer){
		$productId = $observer->getProduct()->getId();
		$visitor = Mage::getSingleton('core/session')->getVisitorData();
		$model = Mage::getModel('getdata/changecart')
				->setUserId($visitor['customer_id'])
				->setVisitorId($visitor['visitor_id'])
				->setProductId($productId)
				->setType(1)
				->setCreatedTime(now())
				;

		try{
			$model->save();
		}catch(Exception $e){

		}
		return $this;
	}

	public function removeItem($observer){
		$item = $observer->getQuoteItem();
		$visitor = Mage::getSingleton('core/session')->getVisitorData();
		$model = Mage::getModel('getdata/changecart')
				->setUserId($visitor['customer_id'])
				->setVisitorId($visitor['visitor_id'])
				->setProductId($item->getProductId())
				->setType(0)
				->setCreatedTime(now())
				;

		try{
			$model->save();
		}catch(Exception $e){

		}
		return $this;
	}

	public function placeOrder($observer){
		$order = $observer->getOrder();
		$visitor = Mage::getSingleton('core/session')->getVisitorData();
		$items = $order->getAllItems();
		$productIds = array();
		foreach($items as $item){
			$productIds[] = $item->getProductId();
		}

		$model = Mage::getModel('getdata/order')
				->setOrderId($order->getId())
				->setUserId($visitor['customer_id'])
				->setVisitorId($visitor['visitor_id'])
				->setProductIds(implode(',', $productIds))
				->setCreatedTime(now());

		try{
			$model->save();
		}catch(Exception $e){

		}
		return $this;
	}

	public function customerRegister($observer){
		$customer = $observer->getCustomer();
		$customerId = $customer->getId();
		$addressId = $customer->getDefaultBilling();
		$address = Mage::getModel('customer/address')->load($addressId);
		$user = Mage::getModel('getdata/user')->load($customerId);

		if(!$user || !$user->getId()){
			$user->setUserId($customerId)
				->setFirstname($customer->getFirstname())
				->setLastname($customer->getLastname())
				->setEmail($customer->getEmail())
				->setBirthday($customer->getDob())
				->setGender($customer->getGender())
				->setCity($address->getCity())
				->setCountry($address->getCountryId())
				->setState($address->getRegion())
				->setZipcode($address->getPostcode())
				->setJoinedTime(date('Y-m-d H:i:s', strtotime($customer->getCreatedAt())));

			try{
				$user->save();
			}catch(Exception $e){

			}
		}
	}

	public function customerLogin($observer){
		$customer = $observer->getCustomer();
		$customerId = $customer->getId();

		$visitor = Mage::getSingleton('core/session')->getData('getdata_visitor');

		$model = Mage::getModel('getdata/visitoruser')->getCollection()
					->addFieldToFilter('visitor_id', $visitor->getVisitorId())
					->addFieldToFilter('user_id', $customerId)
					->getFirstItem();

		if(!$model || !$model->getId()){
			$model->setVisitorId($visitor->getVisitorId())
					->setUserId($customerId);
		}

		$model->setLoginTime(now());

		try{
			$model->save();

			$visitor->setCustomerId($customerId);
			Mage::getSingleton('core/session')->setData('getdata_visitor', $visitor);

		}catch(Exception $e){

		}
	}

	public function customerLogout($observer){
		$customerId = $observer->getCustomer()->getId();
		$visitor = Mage::getSingleton('core/session')->getData('getdata_visitor');
		$model = Mage::getModel('getdata/visitoruser')->getCollection()
					->addFieldToFilter('visitor_id', $visitor->getVisitorId())
					->addFieldToFilter('user_id', $customerId)
					->getFirstItem();

		$model->setLogoutTime(now());

		try{
			$model->save();

			$visitor->setCustomerId(NULL);
			Mage::getSingleton('core/session')->setData('getdata_visitor', $visitor);

		}catch(Exception $e){

		}
	}

	public function reviewSaveAfter($observer){
		$review = $observer->getObject();

		$data = $review->getData();
        $newRatings = $data['ratings'];

        $newSumRatings = 0;
        foreach($newRatings as $r) {
            $value = $r % 5;
            $newSumRatings += ($value) ? $value : 5;
        }

		$newAvgRating = $newSumRatings/count($newRatings);

		$visitor = Mage::getSingleton('core/session')->getData('getdata_visitor');
		$model = Mage::getModel('getdata/review')
				->setReviewId($data['review_id'])
				->setUserId($data['customer_id'])
				->setVisitorId($visitor->getVisitorId())
				->setProductId($data['entity_pk_value'])
				->setRatingValue($newAvgRating)
				->setSummaryReivew($data['title'])
				->setReview($data['review'])
				->setCreatedTime($data['created_at']);

		try{
			$model->save();
		}catch(Exception $e){
		}
	}

	public function productSave($observer){
		$item = $observer->getProduct();

		$productData = array(
			'product_name' => $item->getName(),
			'sku'	=> $item->getSku(),
			'price' => $item->getPrice(),
			'special_price' => $item->getSpecialPrice(),
			'short_description' => $item->getShortDescription(),
			'description' => $item->getDescription(),
			'status' => $item->getStatus(),
		);

		try{
			$product = Mage::getModel('getdata/product')->load($item->getId());
			if(!$product || !$product->getId())
				$product->setProductId($item->getId());

			$product->addData($productData)
				->save();

		}catch(Exception $e){

		}
	}

    /**
     * update data processing
     * copy old data by cron job
     */
    public function updateDataProcessing(){
        $_MAX_SIZE_RECORD = 100;
        $hepler = Mage::helper('getdata');
        //products
        $processing = $hepler->getStatusProcess('products');
        if($processing->getId() && $processing->getRecordsIncrement() <= $processing->getRecordsTotals()){
            $hepler->getProducts((int)$processing->getRecordsIncrement(), $_MAX_SIZE_RECORD);
        }
        //users
        $processing = $hepler->getStatusProcess('users');
        if($processing->getId() && $processing->getRecordsIncrement() <= $processing->getRecordsTotals()){
            $hepler->getUsers((int)$processing->getRecordsIncrement(), $_MAX_SIZE_RECORD);
        }
        //reviews
        $processing = $hepler->getStatusProcess('reviews');
        if($processing->getId() && $processing->getRecordsIncrement() <= $processing->getRecordsTotals()){
            $hepler->getReviews((int)$processing->getRecordsIncrement(), $_MAX_SIZE_RECORD);
        }
        //orders
        $processing = $hepler->getStatusProcess('orders');
        if($processing->getId() && $processing->getRecordsIncrement() <= $processing->getRecordsTotals()){
            $hepler->getOrders((int)$processing->getRecordsIncrement(), $_MAX_SIZE_RECORD);
        }
        //visitors
        $processing = $hepler->getStatusProcess('visitors');
        if($processing->getId() && $processing->getRecordsIncrement() <= $processing->getRecordsTotals()){
            $hepler->getVisitors((int)$processing->getRecordsIncrement(), $_MAX_SIZE_RECORD);
        }
        //change carts
        $processing = $hepler->getStatusProcess('change_carts');
        if($processing->getId() && $processing->getRecordsIncrement() <= $processing->getRecordsTotals()){
            $hepler->getChangeCarts((int)$processing->getRecordsIncrement(), $_MAX_SIZE_RECORD);
        }
        //product views
        $processing = $hepler->getStatusProcess('product_views');
        if($processing->getId() && $processing->getRecordsIncrement() <= $processing->getRecordsTotals()){
            $hepler->getProductViews((int)$processing->getRecordsIncrement(), $_MAX_SIZE_RECORD);
        }
        //visitor users
        $processing = $hepler->getStatusProcess('visitor_users');
        if($processing->getId() && $processing->getRecordsIncrement() <= $processing->getRecordsTotals()){
            $hepler->getVisitorUsers((int)$processing->getRecordsIncrement(), $_MAX_SIZE_RECORD);
        }

        return $this;
    }
}