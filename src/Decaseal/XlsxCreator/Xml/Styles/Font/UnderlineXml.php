<?php

namespace Decaseal\XlsxCreator\Xml\Styles\Font;


use Decaseal\XlsxCreator\Xml\BaseXml;
use XMLWriter;

class UnderlineXml extends BaseXml{
	function render(XMLWriter $xml, $model = null){
		if (!$model) return;

		$xml->startElement('u');

		switch ($model) {
			case 'double' : $xml->writeAttribute('val', 'double'); break;
			case 'singleAccounting' : $xml->writeAttribute('val', 'singleAccounting'); break;
			case 'doubleAccounting' : $xml->writeAttribute('val', 'doubleAccounting'); break;
		}

		$xml->endElement();
	}
}