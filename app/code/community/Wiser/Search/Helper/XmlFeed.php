<?php
class Wiser_Search_Helper_XmlFeed
{

	public function build_xml(array $products, array $configuration, $products_name = "product")
	{
		$xml_feed = new Wiser_Search_Helper_SimpleXml('<?xml version="1.0" encoding="UTF-8"?><data></data>');
		$configurationXML = $xml_feed->addChild('configuration');
		
		$this->_add_product_fields($configurationXML, $configuration);
			
		$productsXML = $xml_feed->addChild($products_name . "s");
		foreach ($products as $product)
		{
			$xml_product = $productsXML->addChild($products_name);
			$xml_product = $this->_add_product_fields($xml_product, $product);
		}

		return $xml_feed->asXML(); // as_formated_xml
	}

	private function _add_product_fields($xml_product, $product_fields)
	{
		foreach($product_fields as $field_name => $field_value)
		{
            if (strpos($field_name, '_html') !== false) {
                $field_value = $this->_clean_html($field_value);
            } else {
                $field_value = $this->_clean_string($field_value);
            }
			$field_value = $this->_clean_string($field_value);
			$product = $xml_product->addChild($field_name, NULL);
			$product->add_cdata($field_value);
		}

		return $xml_product;
	}
    
    private function _clean_html($html)
    {
        $html = htmlentities($html);
        $html = iconv("UTF-8","UTF-8//IGNORE",str_replace(array('"',"\r\n","\n","\r","\t"), array(""," "," "," ",""), $html));
        return $html;
    }
    
	private function _clean_string($string)
	{
		$string = strip_tags($string);
        // iconv removes non utf8 characters this way
		$string = iconv("UTF-8","UTF-8//IGNORE",str_replace(array('"',"\r\n","\n","\r","\t"), array(""," "," "," ",""), $string));
		return $string;
	}
}
?>