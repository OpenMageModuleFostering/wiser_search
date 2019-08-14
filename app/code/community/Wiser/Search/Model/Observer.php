<?php
class Wiser_Search_Model_Observer
{
	private $_Products;
	private $_ProductIds;
	
	public function cacheRefreshType($observer) {
		$stores = Mage::app()->getStores();

		foreach ($stores as $store)
		{
			$storeId 			= $store->getStoreId();
			$Feed 				= new Wiser_Search_Helper_XmlFeed();
			$_Products 			= array();
			$_Configuration 	= array();
			
			// Product feed
			$this->_buildProductsArray($storeId);
			
			foreach($this->_ProductIds as $iProduct)
			{
				array_push($_Products, Wiser_Search_Helper_ProductData::_getProductData($iProduct, $storeId));
			}
									
			$xmlFeed = $Feed->build_xml($_Products, $_Configuration);

			// API Call to push XML to Webhook
			$this->webhookUpdateProduct($xmlFeed, "POST");
		}
		
	}
	
	private function _buildProductsArray($storeId)
	{
		$this->_Products = Mage::getModel('catalog/product')->setStoreId($storeId)->getCollection();
		$this->_Products->addAttributeToFilter('status', 1);//enabled
		$this->_Products->addAttributeToFilter('visibility',  array('gt' => 2));// search only OR catalog, search
		$this->_Products->addAttributeToSelect('*');
		$this->_ProductIds = $this->_Products->getAllIds();
	}
	
	public function productAfterSave($observer){
		$product = $observer->getProduct();
  
        $stores = Mage::app()->getStores();
        $Feed 				= new Wiser_Search_Helper_XmlFeed();
        $_Products 			= array();
        $_Configuration		= array();
        
        foreach ($stores as $store)
        {
            array_push($_Products, Wiser_Search_Helper_ProductData::_getProductData($product->getId(), $store->getStoreId()));
        }
        
        $xmlFeed = $Feed->build_xml($_Products, $_Configuration);

        // API Call to push XML to Webhook
        $this->webhookUpdateProduct($xmlFeed, "POST");
	}
	
	public function productAfterDelete($observer) {
		$product = $observer->getProduct();
		
		$Feed 				= new Wiser_Search_Helper_XmlFeed();
		$_Products 			= array(array("id" => $product->getId()));
		$_Configuration		= array();
		
		$xmlFeed = $Feed->build_xml($_Products, $_Configuration);
		
		// API Call to push XML to Webhook
		$this->webhookUpdateProduct($xmlFeed, "DELETE");
	}
	
	public function validateConfiguration($observer) {
		// Not yet installed, and login to backend
		if( Mage::getSingleton('admin/session')->getUser() !== NULL && Mage::getStoreConfig('wiser_search/wiser_search_group/installed') !== "1") {
			// 
			$this->callInstallUrl();
		}
	}
	
	private function callInstallUrl(){
		$target_url 	= "http://search.wiser.nl/wisersearch_webhook.aspx";
		
		$user 			= Mage::getSingleton('admin/session')->getUser();
		
		$post   		= array(
			'firstname'	=> $user->getFirstname(),
			'lastname'	=> $user->getLastname(),
			'email'		=> $user->getEmail(),
			'domain'	=> Mage::getBaseUrl (Mage_Core_Model_Store::URL_TYPE_WEB),
			'feed_url'	=> Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . "wiser_search/"
        );
        
        $header 		= array(
            //    "Authorization: Basic " . base64_encode( $apikey . ":" ),
        );
     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_URL,$target_url);
        curl_setopt($ch, CURLOPT_POST,1);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        
        $result=curl_exec ($ch);
        
        $status =  curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close ($ch);
	
		if( $status == 200) {
			$data 	= json_decode($result);
            
            if( $data !== NULL ) {
                Mage::getModel('core/config')->saveConfig('wiser_search/wiser_search_group/api_key', $data->api_key);
                Mage::getModel('core/config')->saveConfig('wiser_search/wiser_search_group/script', $data->script);
                Mage::getModel('core/config')->saveConfig('wiser_search/wiser_search_group/webhook', $data->webhook);
                
                Mage::getModel('core/config')->saveConfig('wiser_search/wiser_search_group/installed', 1);
                
                $stores = Mage::app()->getStores();
                foreach ($stores as $store) {
                    $store->resetConfig();
                }
            }
		}
	}
	
	private function webhookUpdateProduct( $feed, $type ) {
		$target_url 	= Mage::getStoreConfig('wiser_search/wiser_search_group/webhook');
       
        $header 		= array(
            "Authorization: Basic " . base64_encode( Mage::getStoreConfig('wiser_search/wiser_search_group/api_key') . ":" ),
            "Content-type: text/plain"
        );
     
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_URL,$target_url);
		if( $type == "DELETE" ) {
			 curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
		} else {
			curl_setopt($ch, CURLOPT_POST,1);
		}
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $feed);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        
        $result=curl_exec ($ch);
        
        $status =  curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close ($ch);
	
		if( $status == 200) {
		} 
        
	}
}
?>