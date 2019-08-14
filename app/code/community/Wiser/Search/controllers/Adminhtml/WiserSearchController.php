<?php
class Wiser_Search_Adminhtml_WiserSearchController extends Mage_Adminhtml_Controller_Action
{
    /**
     * Check if wiser installation was successfull, if not request reinstall
     *
     * @return void
     */
    public function checkAction()
    {
		$target_url 	= Mage::getStoreConfig('wiser_search/wiser_search_group/webhook') . '?mode=reinstall';
       
        $header 		= array(
            "Authorization: Basic " . base64_encode( Mage::getStoreConfig('wiser_search/wiser_search_group/api_key')),
        //    "Content-type: text/plain"
        );
     
        $ch = curl_init();
        //curl_setopt($ch, CURLOPT_SAFE_UPLOAD, false);
        curl_setopt($ch, CURLOPT_URL,$target_url);
		//curl_setopt($ch, CURLOPT_GET,1);
        
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        //curl_setopt($ch, CURLOPT_POSTFIELDS, $feed);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
        
        $result=curl_exec ($ch);
        
        $status =  curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close ($ch);
	
		if( $status == 200) {
			//correct response
		} 
        Mage::app()->getResponse()->setBody($status);
    }
}