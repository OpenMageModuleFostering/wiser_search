<?php
class Wiser_Search_IndexController extends Mage_Core_Controller_Front_Action {
	private $_Products;
	private $_ProductIds;

	public function indexAction() 
	{
		set_time_limit(3600);
		
		$auth = $this -> getRequest() -> getHeader('Authorization');
		
		if( $auth !== false) {
			$authData  	= explode(" ", $auth);
			$user		= explode(":", base64_decode($authData[1]));
			
			if( $user[0] == Mage::getStoreConfig('wiser_search/wiser_search_group/api_key')) {
				$Feed 				= new Wiser_Search_Helper_XmlFeed();
				$_Products 			= array();
				$_Configuration 	= array();
				
				if( isset($_GET['store'] )) {
					$storeId = (int)$_GET['store'];
					
					if( isset($_GET['mode']) && $_GET['mode'] == 'content' ) {
						// Content / blog feed
						
						$helper = Mage::helper('cms');
						$processor = $helper->getPageTemplateProcessor();

						foreach( Mage::getModel('cms/page')->getCollection()->addStoreFilter($storeId) as $iPage ){
							$_Page = array();
							
							$_Page['id'] = $iPage->getId();
							
							$_Page['created_at'] = $iPage->getCreationTime();
							$_Page['updated_at'] = $iPage->getUpdateTime();
							$_Page['active'] = $iPage->getIsActive();
							$_Page['sort'] = $iPage->getSortOrder();
							
							$_Page['title'] = $iPage->getTitle();
							$_Page['description'] = $iPage->getMetaDescription();
							$_Page['keywords'] = $iPage->getMetaKeywords();

							$_Page['content'] = $processor->filter($iPage->getContent());
							//$iPage->getIdentifier()
							$_Page['url'] = Mage::helper('cms/page')->getPageUrl($iPage->getId());
							
							array_push($_Products,$_Page);
						}
						$xmlFeed = $Feed->build_xml($_Products, $_Configuration, "page");
					} else {
						// Product feed
						$this->_buildProductsArray($storeId, isset($_GET['pagenr']) ? (int)$_GET['pagenr'] : 1);
						
						foreach($this->_ProductIds as $iProduct)
						{
							array_push($_Products, Wiser_Search_Helper_ProductData::_getProductData($iProduct, $storeId));
						}
						
						//$store = Mage::getModel('core/store')->load($storeId);
												
						$xmlFeed = $Feed->build_xml($_Products, $_Configuration);
					}
					
				} else {
					// Store feed
					$stores = Mage::app()->getStores();
					foreach ($stores as $store)
					{
						$_Store = array();
						 
						// Gets the current store's id
						$_Store['id'] = $store->getStoreId();
						 
						// Gets the current store's code
						$_Store['code'] = $store->getCode();
						 
						// Gets the current website's id
						$_Store['websiteid'] = $store->getWebsiteId();
						 
						// Gets the current store's group id
						$_Store['groupid'] = $store->getGroupId();
						 
						// Gets the current store's name
						$_Store['name'] = $store->getFrontendName();
						 
						// Gets the current store's sort order
						$_Store['sort'] = $store->getSortOrder();
						 
						// Gets the current store's status
						$_Store['active'] = $store->getIsActive();
						 
						// Gets the current store's locale
						$_Store['locale'] = substr(Mage::getStoreConfig('general/locale/code', $store->getId()),0,2);
						 
						// Gets the current store's home url
						$_Store['productfeed'] = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . "wiser_search/?store=" . $store->getStoreId();
						$_Store['contentfeed'] = $store->getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . "wiser_search/?store=" . $store->getStoreId() ."&mode=content";

						$_Store['currency'] = $store->getCurrentCurrencyCode();
                        
                        $this->_buildProductsArray($store->getStoreId(), -1);
                        
                        $_Store['productcount'] = count($this->_ProductIds);
						
						array_push($_Products, $_Store);
					}
                    
                    $_Configuration['php_version'] = phpversion();
                    $_Configuration['platform'] = "Magento";
                    $_Configuration['platform_version'] = Mage::getVersion();
					
					$xmlFeed = $Feed->build_xml($_Products, $_Configuration, "shop");
				}
				
				$this->_sendHeader();
				echo $xmlFeed;
				exit();
			}
		}
		
		$this->getResponse()->setHttpResponseCode(401);
	}
	
	private function _buildProductsArray($storeId, $pageNr=-1)
	{
		$this->_Products = Mage::getModel('catalog/product')->getCollection();
        
        $this->_Products->setStoreId($storeId)->addWebsiteFilter(Mage::getModel('core/store')->load($storeId)->getWebsiteId());
		$this->_Products->addAttributeToFilter('status', 1);//enabled
		$this->_Products->addAttributeToFilter('visibility',  array('gt' => 2));// search only OR catalog, search
		$this->_Products->addAttributeToSelect('*');
        
        // All products
        if( $pageNr == -1 ) {
            $this->_ProductIds = $this->_Products->getAllIds();
        } else {
            $itemsPerPage = 100; 
            $this->_ProductIds = $this->_Products->getAllIds($itemsPerPage, ($pageNr - 1) * $itemsPerPage); /* items, offset */
        }
	}
	
	private function _sendHeader() 
	{
		header('Content-type: text/xml');
	}
}
