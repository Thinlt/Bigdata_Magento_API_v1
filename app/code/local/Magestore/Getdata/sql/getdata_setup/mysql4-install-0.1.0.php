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

/** @var $installer Mage_Core_Model_Resource_Setup */
$installer = $this;

$installer->startSetup();

/**
 * create table
 */
$installer->run("
DROP TABLE IF EXISTS {$this->getTable('getdata_user')};
CREATE TABLE {$this->getTable('getdata_user')} (
	`user_id` int(11) unsigned NOT NULL,
	`firstname` varchar(30) NOT NULL default '',
	`lastname` varchar(30) NOT NULL default '',
	`email` varchar(100) NOT NULL default '',
	`birthday` date NULL,
	`gender` tinyint(1) NOT NULL default '0',
	`city` varchar(100) NULL,
	`country` varchar(100) NULL,
	`state` varchar(100) NULL,
	`zipcode` varchar(10) NULL,
	`joined_time` datetime NULL,
	`last_login_time` datetime NULL,
	PRIMARY KEY (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('getdata_product')};
CREATE TABLE {$this->getTable('getdata_product')} (
	`product_id` int(11) unsigned NOT NULL,
	`product_name` varchar(255) NOT NULL default '',
	`sku` varchar(30) NOT NULL default '',
	`short_description` text NOT NULL default '',
	`description` text NOT NULL default '',
	`price` float(12,4) NOT NULL default '0',
	`special_price` float(12,4) NULL,
	`status` tinyint(1) NOT NULL default '1',
	PRIMARY KEY (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('getdata_review')};
CREATE TABLE {$this->getTable('getdata_review')} (
	`review_id` int(11) unsigned NOT NULL,
	`user_id` int(11) unsigned NOT NULL,
	`visitor_id` bigint(20) unsigned NOT NULL,
	`product_id` int(11) unsigned NOT NULL,
	`rating_value` tinyint(1) NOT NULL default '0',
	`title` varchar(255) NOT NULL default '',
	`review` text NOT NULL default '',
	`created_time` datetime NULL,
	PRIMARY KEY (`review_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('getdata_order')};
CREATE TABLE {$this->getTable('getdata_order')} (
	`order_id` int(11) unsigned NOT NULL,
	`user_id` int(11) unsigned NOT NULL,
	`visitor_id` bigint(20) unsigned NOT NULL,
	`product_ids` varchar(255) NOT NULL,
	`created_time` datetime NULL,
	PRIMARY KEY (`order_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('getdata_changecart')};
CREATE TABLE {$this->getTable('getdata_changecart')} (
	`changecart_id` int(11) unsigned NOT NULL auto_increment,
	`user_id` int(11) unsigned NOT NULL,
	`visitor_id`	bigint(20) unsigned NOT NULL,
	`product_id` int(11) unsigned NOT NULL,
	`type` tinyint(1) NOT NULL default '1',
	`created_time` datetime NULL,
	PRIMARY KEY (`changecart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

DROP TABLE IF EXISTS {$this->getTable('getdata_viewproduct')};
CREATE TABLE {$this->getTable('getdata_viewproduct')} (
	`viewproduct_id` bigint(20) unsigned NOT NULL auto_increment,
	`product_id` int(11) unsigned NOT NULL,
	`user_id` int(11) unsigned NOT NULL,
	`visitor_id` bigint(20) unsigned NOT NULL,
	`start_viewed_time` datetime NULL,
	`end_viewed_time` datetime NULL,
	`previous_product_id` int(11) unsigned NOT NULL default '0',
	PRIMARY KEY (`viewproduct_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS {$this->getTable('getdata_visitor')};
CREATE TABLE {$this->getTable('getdata_visitor')} (
	`visitor_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`session_id` varchar(64) NOT NULL,
	`first_visit_time` datetime  NOT NULL,
	`last_visit_time` datetime  NOT NULL,
	`last_url_id` bigint(10) unsigned NULL,
	`http_referer` varchar(255) NULL,
	`http_user_agent` varchar(255) NULL,
	`remote_addr` varchar(20),
	PRIMARY KEY (`visitor_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS {$this->getTable('getdata_visitoruser')};
CREATE TABLE {$this->getTable('getdata_visitoruser')} (
	`visitoruser_id` bigint(20) unsigned NOT NULL AUTO_INCREMENT,
	`visitor_id` bigint(20) unsigned NOT NULL,
	`user_id` int(11) unsigned NOT NULL,
	`login_time` datetime  NOT NULL,
	`logout_time` datetime  NOT NULL,
	UNIQUE(`visitor_id`, `user_id`),
	PRIMARY KEY (`visitoruser_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;


DROP TABLE IF EXISTS {$this->getTable('getdata_data_processing')};
CREATE TABLE {$this->getTable('getdata_data_processing')} (
    `processing_id` int(3) unsigned NOT NULL AUTO_INCREMENT,
	`data_table` varchar(255) NULL,
	`record_no` int(11) unsigned NULL,
	`records_increment` int(11) unsigned NULL,
	`records_totals` int(11) unsigned NULL,
	`updated_at` datetime  NULL,
	PRIMARY KEY (`processing_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8;

");

//$hepler = Mage::helper('getdata');
//
//$hepler->getProducts();
//$hepler->getUsers();
//$hepler->getReviews();
//$hepler->getOrders();
//$hepler->getVisitors();
//$hepler->getChangeCarts();
//$hepler->getProductViews();
//$hepler->getVisitorUsers();
$installer->endSetup();

