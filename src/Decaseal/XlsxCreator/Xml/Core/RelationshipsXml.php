<?php

namespace Decaseal\XlsxCreator\Xml\Core;

use Decaseal\XlsxCreator\Xml\BaseXml;
use XMLWriter;

class RelationshipsXml extends BaseXml{
	function render(XMLWriter $xml, $model = null){
		if (is_null($model)) return;

		$xml->startDocument('1.0', 'UTF-8', 'yes');
		$xml->startElement('Relationships');

		$xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/package/2006/relationships');

		$relationshipXml = new RelationshipXml();
		foreach ($model as $relationship) $relationshipXml->render($xml, $relationship);

		$xml->endElement();
		$xml->endDocument();
	}
}