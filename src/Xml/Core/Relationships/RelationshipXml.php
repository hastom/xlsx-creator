<?php

namespace Topvisor\XlsxCreator\Xml\Core\Relationships;

use Topvisor\XlsxCreator\Xml\BaseXml;
use XMLWriter;

class RelationshipXml extends BaseXml{
	function render(XMLWriter $xml, array $model = null){
		if (is_null($model)) return;

		$xml->startElement('Relationship');

		foreach ($model as $name => $value) $xml->writeAttribute($name, $value);

		$xml->endElement();
	}
}