<?php

class Wiser_Search_Helper_SimpleXml extends SimpleXMLElement
{

	public function add_cdata( $cdata_text )
	{
		$node = dom_import_simplexml( $this );
		$no = $node->ownerDocument;
		$node->appendChild( $no->createCDATASection( $cdata_text ) );
	}

	public function as_formated_xml()
	{
		$xml_string = $this->asXML();
		$dom = new DOMDocument( '1.0', 'UTF-8' );
		$dom->loadXML( $xml_string );
		$dom->preserveWhiteSpace = FALSE;
		$dom->formatOutput = TRUE;
		return $dom->saveXML();
	}
}