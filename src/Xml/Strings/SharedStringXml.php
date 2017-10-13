<?php

namespace Topvisor\XlsxCreator\Xml\Strings;

use Topvisor\XlsxCreator\Structures\Values\Value;
use Topvisor\XlsxCreator\Xml\BaseXml;
use XMLWriter;

class SharedStringXml extends BaseXml{
	function render(XMLWriter $xml, array $model = null){
		if (!$model) return;

		$xml->startElement('si');

		switch ($model['type']) {
			case Value::TYPE_STRING: (new TextXml())->render($xml, $model); break;
			case Value::TYPE_RICH_TEXT: (new RichTextXml())->render($xml, $model); break;
		}

		$xml->endElement();
	}
}