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
 * Getdata Helper
 * 
 * @category    Magestore
 * @package     Magestore_Getdata
 * @author      Magestore Developer
 */
class Magestore_Getdata_Helper_Data extends Mage_Core_Helper_Abstract
{
    public function getChangeCarts($page, $size){
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		$tableLogInfo = $resource->getTableName('log_url_info');
		$tableLog = $resource->getTableName('log_url');
		$tableLogCustomer = $resource->getTableName('log_customer');
		
		$query = "SELECT * FROM $tableLogInfo 
				LEFT JOIN $tableLog ON $tableLog.url_id = $tableLogInfo.url_id 
				LEFT JOIN $tableLogCustomer ON $tableLogCustomer.visitor_id = $tableLog.visitor_id
				HAVING url LIKE '%/checkout/cart/add/%' ";
				
		if($page && $size){
			$query .= " LIMIT ($page-1)*$size, $size";
		}
		$results = $readConnection->fetchAll($query);
		//print_r($results);
		$changeCarts = array();
		foreach($results as $result){
			$regexPattern = "/\/product\/(\d)\//";
			preg_match($regexPattern, $result['url'], $match);
			
			$changeCart = array(
				//$result['url_id'],
				'user_id' => $result['customer_id'],
				'visitor_id' => $result['visitor_id'],
				'product_id' => $match[1], //product id
				'type' => 1, //type
				'created_time' => $result['visit_time'],
			);	
			Mage::getModel('getdata/changecart')->setData($changeCart)
				->save();
			$changeCarts[] = $changeCart;
		}
		
		return $changeCarts;
	}
	
	public function getOrders($page, $size){
		//return;
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		
		$tableItem = $resource->getTableName('sales/order_item');
		$tableLogInfo = $resource->getTableName('log_url_info');
		$tableLog = $resource->getTableName('log_url');
		$tableLogVisitorInfo = $resource->getTableName('log_visitor_info');
		$tableLogCustomer = $resource->getTableName('log_customer');
		
		$collection = Mage::getModel('sales/order')->getCollection();
		$collection->getSelect()
				->joinLeft(array('item' => $tableItem),
						"main_table.entity_id = item.order_id",
						array('product_ids' => "GROUP_CONCAT(item.product_id SEPARATOR ',')"))
				
				->joinLeft(array('log_customer' => $tableLogCustomer),
						"main_table.customer_id = log_customer.customer_id",
						array('visitor_id'))
						
				/*->joinLeft(array('log_visitor' => $tableLogVisitorInfo),
						"main_table.remote_id = INET_NTOA(log_visitor.remote_addr)",
						array())
						
				->joinLeft(array('log_info' => $tableLogInfo),
						"main_table.created_at = item.order_id",
						array('product_ids' => "GROUP_CONCAT(product_id SEPARATOR ', ')"))*/
				->group('order_id')
				//->having('MAX(log_customer.visitor_id)')
				;
		
		if($page && $size)
			$collection->setPageSize($size)
				->setCurPage($page);
				
		$orders = array();
		foreach($collection as $order){
			$productIds = array_unique(explode(',', $order->getProductIds()));
			$order = array(
				'order_id' => $order->getId(),
				'user_id' => $order->getCustomerId(),
				'visitor_id' => $order->getVisitorId(),
				'product_ids' => implode(',', $productIds),
				'created_time' => $order->getCreatedAt()
			);

			Mage::getModel('getdata/order')->setData($order)
				->save();
			$orders[] = $order;
		}
		return($orders);
	}
	
	public function getProductViews($page, $size){
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');
		$tableLogInfo = $resource->getTableName('log_url_info');
		$tableLog = $resource->getTableName('log_url');
		$tableLogCustomer = $resource->getTableName('log_customer');
		$tableUrlRewrite = $resource->getTableName('core_url_rewrite');
		
		$query = "SELECT * FROM $tableLogInfo 
				LEFT JOIN $tableLog ON $tableLog.url_id = $tableLogInfo.url_id 
				LEFT JOIN $tableLogCustomer ON $tableLogCustomer.visitor_id = $tableLog.visitor_id
				LEFT JOIN $tableUrlRewrite ON $tableLogInfo.referer LIKE CONCAT('%/', $tableUrlRewrite.request_path) 
									AND $tableUrlRewrite.product_id IS NOT NULL
				HAVING url LIKE '%/catalog/product/view/id/%' ";

		if($page && $size){
			$query .= " LIMIT ($page-1)*$size, $size";
		}
		$results = $readConnection->fetchAll($query);
		
		$productViews = array();
		foreach($results as $item){
			//print_r($item);die();
			$regexPattern = "/\/catalog\/product\/view\/id\/(\d)\//";
			preg_match($regexPattern, $item['url'], $match);
			
			preg_match($regexPattern, $item['referer'], $match1);
			if($match1[1]){
				$previousProductId = $match1[1];
			}elseif($item['product_id']){
				$previousProductId = $item['product_id'];
			}else
				$previousProductId = NULL;
			
			$productView = array(
				//$item['url_id'],
				'product_id' => $match[1], //productid
				'user_id' => $item['customer_id'],
				'visitor_id' => $item['visitor_id'],
				'start_viewed_time' => $item['visit_time'],
				'end_viewed_time' => NULL, //end view time
				'previous_product_id' => $previousProductId,
			);
			Mage::getModel('getdata/viewproduct')->setData($productView)
					->save();
			
			$productViews[] = $productView;
		}
		return ($productViews);
	}
	
	public function getVisitorUsers($page, $size){
		
		$resource = Mage::getSingleton('core/resource');
		$readConnection = $resource->getConnection('core_read');	
		$tableLogCustomer = $resource->getTableName('log_customer');
		
		$query = "SELECT * FROM $tableLogCustomer";

		if($page && $size){
			$query .= " LIMIT ($page-1)*$size, $size";
		}
		$results = $readConnection->fetchAll($query);
		
		$visitorUsers = array();
		
		foreach($results as $item){
			$visitorUser = array(
				'visitor_id' => $item['visitor_id'],
				'user_id' => $item['customer_id'],
				'login_time' => $item['login_at'],
				'logout_time' => $item['logout_at'],
			);

			try{
				Mage::getModel('getdata/visitoruser')->setData($visitorUser)
				->save();
				
			}catch(Exception $e){
				
			}
			$visitorUsers[] = $visitorUser;
		}
		
		return $visitorUsers;
	}
	
	public function getVisitors($page, $size){
		$collection = Mage::getModel('log/visitor')->getCollection();
		
		$collection->getSelect()
			->joinLeft(array('visitorInfo' => Mage::getSingleton('core/resource')->getTableName('log_visitor_info')), 
				'visitorInfo.visitor_id = main_table.visitor_id', 
				array('http_referer' => 'http_referer', 'http_user_agent' => 'http_user_agent', 
					'remote_addr' => 'remote_addr'))
		;
		
		if($page && $size)
			$collection->setPageSize($size)
				->setCurPage($page);
		
		$visitors = array();	
		foreach($collection as $item){
			$visitor = array(
				//$item->getId(),
				'session_id' => $item->getSessionId(),
				'first_visit_time' => $item->getFirstVisitAt(),
				'last_visit_time' => $item->getLastVisitAt(),
				'last_url_id' => $item->getLastUrlId(),
				'http_referer' => $item->getHttpReferer(),
				'http_user_agent' => $item->getHttpUserAgent(),
				'remote_addr' => long2ip($item->getRemoteAddr()),
			);
			
			Mage::getModel('getdata/visitor')->setData($visitor)
					->save();
			$visitors[] = $visitor;
		}
		
		return $visitors;
	}
	
	public function getReviews($page, $size){
		$collection = Mage::getModel('review/review')->getCollection();
		$coreResource =  Mage::getSingleton('core/resource');
		$collection->getSelect()
			->joinLeft(array('rating' => $coreResource->getTableName('rating_option_vote')), 
				'rating.review_id = main_table.review_id', 
				array('value' => 'AVG(rating.value)'))
			
			/*	
			->join(array('log_info' => $coreResource->getTableName('log_url_info')),
				"log_info.url LIKE CONCAT ('%/review/product/post/id/', main_table.entity_pk_value, '/')
				
				",
				array())
			
			->join(array('log' => $coreResource->getTableName('log_url')),
				"log.url_id=log_info.url_id 
				AND log.visit_time >= main_table.created_at 
				AND log.visit_time <= main_table.created_at + INTERVAL 10 SECOND",
				array('log.visitor_id'))
				*/
			->group('main_table.review_id')
			;
		
		//$collection->printLogQuery(true);die();
		if($page && $size)
			$collection->setPageSize($size)
				->setCurPage($page);
		
		
		//$collection->printLogQuery(true);die();
						
		$reviews = array();
		foreach($collection as $item){
			$review = array(
				'review_id' => $item->getId(),
				'user_id' => $item->getCustomerId(),
				'visitor_id' => $item->getVisitorId(),
				'product_id' => $item->getEntityPkValue(), //product_id
				'title' => $item->getTitle(), 
				'review' => $item->getDetail(),//review
				'rating_value' => $item->getValue(),
				'created_time' => $item->getCreatedAt(),
				
			);
			$review = Mage::getModel('getdata/review')->setData($review)
					->save();
			$reviews[] = $review;
		}
		
		return($reviews);
	}
	
	public function getProducts($page, $size){
		$collection = Mage::getModel('catalog/product')->getCollection()
				->addAttributeToSelect('name')
				->addAttributeToSelect('sku')
				->addAttributeToSelect('price')
				->addAttributeToSelect('special_price')
				->addAttributeToSelect('short_description')
				->addAttributeToSelect('description')
				->addAttributeToSelect('status');
		
		if($page && $size)
			$collection->setPageSize($size)
				->setCurPage($page);
		
		$products = array();
		foreach($collection as $item){
			$product = array(
				'product_id' => $item->getId(),
				'product_name' => $item->getName(),
				'sku'	=> $item->getSku(),
				'price' => $item->getPrice(),
				'special_price' => $item->getSpecialPrice(),
				'short_description' => $item->getShortDescription(),
				'description' => $item->getDescription(),
				'status' => $item->getStatus(),
			);
			$product = Mage::getModel('getdata/product')->setData($product)
					->save();
			$products[] = $product;
		}
		
		return $products;
	}
	
	public function getUsers($page, $size){
		$collection = Mage::getModel('customer/customer')->getCollection()
			->addAttributeToSelect('firstname')
			->addAttributeToSelect('lastname')
			->addAttributeToSelect('email')
			->addAttributeToSelect('gender')
			->addAttributeToSelect('dob')
			->addAttributeToSelect('created_at')
			//->addAttributeToSelect('login_at')
			
			->joinAttribute('billing_postcode', 'customer_address/postcode', 'default_billing', null, 'left')
			->joinAttribute('billing_city', 'customer_address/city', 'default_billing', null, 'left')
			//->joinAttribute('billing_telephone', 'customer_address/telephone', 'default_billing', null, 'left')
			->joinAttribute('billing_region', 'customer_address/region', 'default_billing', null, 'left')
			->joinAttribute('billing_country_id', 'customer_address/country_id', 'default_billing', null, 'left')
			
			;
		
		$collection->getSelect()
			->joinLeft(array('c_log' => $collection->getTable('log/customer')), 
				'c_log.customer_id=e.entity_id', 
				array('last_login' => 'MAX(c_log.login_at)'))
				
			->group('e.entity_id')
			//->having('MAX(c_log.login_at)')
			;
		//$collection->printLogQuery(true);die();
		if($page && $size)
			$collection->setPageSize($size)
				->setCurPage($page);	
		
		$customers = array();
		foreach($collection as $item){
			$customer = array(
				'user_id' => $item->getId(),
				'firstname' => $item->getFirstname(),
				'lastname' => $item->getLastname(),
				'email' => $item->getEmail(),
				'gender' => $item->getGender(),
				'dob' => $item->getDob(),
				'zipcode' => $item->getBillingPostcode(),
				'city' => $item->getBillingCity(),
				'state' => $item->getBillingRegion(), //state
				'country' => $item->getBillingCountryId(),
				'joined_time' => date('Y-m-d H:i:s', strtotime($item->getCreatedAt())),
				'last_login_time' => $item->getLastLogin(),
			);
			
			$customers[] = $customer;
			$user = Mage::getModel('getdata/user')->setData($customer)
					->save();
		}
		
		return ($customers);
	}
}