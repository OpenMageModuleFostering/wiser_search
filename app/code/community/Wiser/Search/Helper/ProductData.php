<?php
class Wiser_Search_Helper_ProductData
{

	public static function _getProductData($ProductInput, $storeId)
	{
		$Product = Mage::getModel('catalog/product')->setStoreId($storeId)->load($ProductInput);
        
		$Cats = self::_getCategories($Product, $storeId);
		$Data = array();
		$Data['id']=$ProductInput;
		$Data['storeid'] = $storeId;
		$Data['title']=$Product->getName();
		$Data['description']=strip_tags($Product->getDescription());
		$Data['price']=$Product->getPrice();
		$Data['special_price']=$Product->getSpecialPrice();
		$Data['link']=$Product->getProductUrl();
		$Data['image_link']= $Product->getImage() == "no_selection" ? "" : Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_MEDIA).'catalog/product'.$Product->getImage();
		$Data['image_link_medium']= $Product->getImage() == "no_selection" ? "" : (string) Mage::helper('catalog/image')->init(  $Product, 'image')->resize(320, 320);
		$Data['category'] = $Cats['main'];
		$Data['subcategory'] = $Cats['sub'];
		$Data['brand']=$Product->getResource()->getAttribute('manufacturer')->getFrontend()->getValue($Product);
		if($Data['brand'] == "No") {
			$Data['brand'] = "";
		}
        
        if( in_array(Mage::getModel('core/store')->load($storeId)->getWebsiteId(), $Product->getWebsiteIds() ) && $Product->getStatus() == 1 && $Product->getVisibility() > 2  ) {
            
            $default = Mage::getStoreConfig('cataloginventory/item_options/manage_stock', Mage::app()->getStore($storeId));
                        
            if(     ($Product->getStockItem()->getData("use_config_manage_stock") == 1 && $default =='0' ) 
                    || ($Product->getStockItem()->getData("use_config_manage_stock") == 1 && $default =='1' && $Product->getIsInStock()) 
                    || ($Product->getStockItem()->getData("use_config_manage_stock") == 0 && $Product->getStockItem()->getData("manage_stock") == '0' )
                    || ($Product->getStockItem()->getData("use_config_manage_stock") == 0 && $Product->getStockItem()->getData("manage_stock") == '1' && $Product->getIsInStock() ) ) {

                $Data['availability'] = 'yes';
            } else {
               $Data['availability'] = 'no';
            }
        } else {
            $Data['availability'] = 'no';
        }
		
		//$Data['shippingcost'] = $Config->general->shippingcost;
		//$Data['shippingtime'] = $Config->general->shippingtime;

		$attributes = $Product->getAttributes();

		foreach ($attributes as $attribute) 
		{
			//if ($attribute->getIsFilterable()) 
			//{
				if ($attribute->getFrontend()->getConfigField('input')=='multiselect') {
					$value = $attribute->getFrontend()->getOption($Product->getData($attribute->getAttributeCode()));
					if (is_array($value)) {
						$value = implode('~', $value);
					}
					$Data[$attribute->getAttributeCode()] = $value;
				} else {
					$Data[$attribute->getAttributeCode()] = $attribute->getFrontend()->getValue($Product);
				}
			//}
		}

		if($Data['special_price'] == '')
		{
			$Data['special_price'] = $Data['price'];
		}

		return $Data;
	}

	public static function _getCategories($Product, $storeId)
	{
		$Ids = $Product->getCategoryIds();

		$Categories = array();
		
		$main = array();
		$sub = array();
		
		foreach($Ids as $Category)
		{
			$CategoryModel = Mage::getModel('catalog/category')->setStoreId($storeId)->load($Category);
			/*if ($CategoryModel->getLevel() != '3' && $CategoryModel->getLevel() != '4')
			{
				continue;
			}
			*/
			$catnames = array();
			
			foreach ($CategoryModel->getParentCategories() as $parent) {
				$catnames[] = $parent->getName();
			}
			
			if(count($catnames) > 0 ){
				array_push($main, $catnames[0]);
			} else {
				array_push($main, "");
			}
			if(count($catnames) > 1 ){
				array_push($sub, $catnames[1]);
			} else {
				array_push($sub, "");
			}			
		}
		
		$Categories['main'] = implode("~", $main);
		$Categories['sub']  = implode("~", $sub);
		

		return $Categories;
	}
}

?>