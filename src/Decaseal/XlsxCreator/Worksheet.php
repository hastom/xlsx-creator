<?php

namespace Decaseal\XlsxCreator;

use Decaseal\XlsxCreator\Xml\ListXml;
use Decaseal\XlsxCreator\Xml\Sheet\HyperlinkXml;
use Decaseal\XlsxCreator\Xml\Sheet\PageMargins;
use Decaseal\XlsxCreator\Xml\Sheet\PageSetupXml;
use Decaseal\XlsxCreator\Xml\Sheet\RowXml;
use Decaseal\XlsxCreator\Xml\Sheet\SheetFormatPropertiesXml;
use Decaseal\XlsxCreator\Xml\Sheet\SheetPropertiesXml;
use XMLWriter;

/**
 * Class Worksheet. Содержит методы для работы с таблицей.
 *
 * @package Decaseal\XlsxCreator
 */
class Worksheet{
	const DY_DESCENT = 55;

	private $workbook;
	private $id;
	private $name;
	private $tabColor;
	private $outlineLevelCol;
	private $outlineLevelRow;
	private $defaultRowHeight;
	private $view;
	private $pageSetup;
	private $autoFilter;
	private $columns;

	private $committed;
	private $lastUncommittedRow;
	private $rows;
	private $merges;
	private $sheetRels;

	private $filename;
	private $xml;

	private $rId;

	/**
	 * Worksheet constructor.
	 *
	 * @param Workbook $workbook - workbook, к которому принадлежит таблица
	 * @param int $id - ID таблицы в $workbook
	 * @param string $name - имя таблицы
	 */
	function __construct(Workbook $workbook, int $id, string $name){
		$this->workbook = $workbook;
		$this->id = $id;
		$this->name = $name;

		$this->tabColor = null;
		$this->outlineLevelCol = 0;
		$this->outlineLevelRow = 0;
		$this->defaultRowHeight = 15;
		$this->view = null;
		$this->pageSetup = [
			'margins' => ['left' => 0.7, 'right' => 0.7, 'top' => 0.75, 'bottom' => 0.75, 'header' => 0.3, 'footer' => 0.3],
			'orientation' => 'portrait',
			'horizontalDpi' => 4294967295,
			'verticalDpi' => 4294967295,
			'fitToPage' => false,
			'blackAndWhite' => false,
			'draft' => false,
			'scale' => 100,
			'fitToWidth' => 1,
			'fitToHeight' => 1,
			'paperSize' => null,
			'showRowColHeaders' => false,
			'showGridLines' => false,
			'horizontalCentered' => false,
			'verticalCentered' => false,
			'rowBreaks' => null,
			'colBreaks' => null
		];
		$this->autoFilter = null;


		$this->committed = false;
		$this->columns = [];
		$this->rows = [];
		$this->merges = [];
		$this->lastUncommittedRow = 1;
		$this->sheetRels = new SheetRels($this);

		$this->filename = $this->workbook->genTempFilename();
		$this->xml = new XMLWriter();
		$this->xml->openURI($this->filename);

		$this->startWorksheet();
	}

	function __destruct(){
		unset($this->workbook);
		unset($this->rows);
		unset($this->merges);
		unset($this->sheetRels);
		unset($this->xml);

		if (file_exists($this->filename)) unlink($this->filename);
	}

	/**
	 * @return Workbook - workbook, к которому принадлежит таблица
	 */
	function getWorkbook() : Workbook{
		return $this->workbook;
	}

	/**
	 * @return int - ID таблицы в $workbook
	 */
	function getId() : int{
		return $this->id;
	}

	/**
	 * @return string - имя таблицы
	 */
	function getName() : string{
		return $this->name;
	}

	/**
	 * @return string|null - цвет вкладки
	 */
	function getTabColor(){
		return $this->tabColor;
	}

	/**
	 * @param string|null $tabColor - цвет вкладки
	 * @return Worksheet - $this
	 */
	function setTabColor(string $tabColor = null) : Worksheet{
		$this->tabColor = $tabColor;
		return $this;
	}

	/**
	 * @return int - worksheet column outline level
	 */
	function getOutlineLevelCol() : int{
		return $this->outlineLevelCol;
	}

	/**
	 * @param int $outlineLevelCol - worksheet column outline level
	 * @return Worksheet - $this
	 */
	function setOutlineLevelCol(int $outlineLevelCol) : Worksheet{
		$this->outlineLevelCol = $outlineLevelCol;
		return $this;
	}

	/**
	 * @return int - worksheet row outline level
	 */
	function getOutlineLevelRow() : int{
		return $this->outlineLevelRow;
	}

	/**
	 * @param int $outlineLevelRow - worksheet row outline level
	 * @return Worksheet - $this
	 */
	function setOutlineLevelRow(int $outlineLevelRow) : Worksheet{
		$this->outlineLevelRow = $outlineLevelRow;
		return $this;
	}

	/**
	 * @return int - высота строк по умолчанию
	 */
	function getDefaultRowHeight() : int{
		return $this->defaultRowHeight;
	}

	/**
	 * @param int $defaultRowHeight - высота строк по умолчанию
	 * @return Worksheet - $this
	 */
	function setDefaultRowHeight(int $defaultRowHeight) : Worksheet{
		$this->defaultRowHeight = $defaultRowHeight;
		return $this;
	}

	/**
	 * @see Worksheet::setView() Параметры view
	 *
	 * @return array|null - представление worksheet
	 */
	function getView(){
		return $this->view;
	}

	/**
	 * Параметры массива $view
	 *
	 * $view['state']				string Состояние представления ('normal', 'split', 'frozen').
	 *
	 * Параметры, общие для всех состояний представления
	 * $view
	 * 		['rightToLeft']			bool Ориентация справа на лево
	 * 		['activeCell']			string Текущая выбранная ячейка (например, 'A1', 'B10', и т.д.)
	 * 		['showRuler']			bool Показывать линейку в макете страницы
	 * 		['showRowColHeaders']	bool Показывать заголовки строк и столбцов (например, A1, B1 вверху и 1,2,3 слева)
	 * 		['showGridLines']		bool Показывать линии сетки
	 * 		['zoomScale']			int Процент увеличения
	 * 		['zoomScaleNormal']		int Нормальное увеличение
	 * 		['view']				string Стиль отображения ('pageBreakPreview', 'pageLayout')
	 *
	 * Параметры frozen представления ("замораживает" несколько строк/столбцов)
	 * $view
	 * 		['xSplit']				int Сколько столбцов "заморожено"
	 * 		['ySplit']				int Сколько строк "заморожено"
	 * 		['topLeftCell']			string Левая-верхняя ячейка в "незамороженной" панели (например, 'D4', 'G15', и т.д.)
	 *
	 * Параметры split представления (делит экран на 2/4 части)
	 * $view
	 * 		['xSplit']				int Количество точек слева до границы
	 * 		['ySplit']				int Количество точек сверху до границы
	 * 		['topLeftCell']			string Левая-верхняя ячейка в нижней правой панели (например, 'A1', 'B10', и т.д.)
	 * 		['activePane']			string Активная панель ('topLeft', 'topRight', 'bottomLeft', 'bottomRight')
	 *
	 * @param array|null $view - представление worksheet
	 * @return Worksheet - $this
	 */
	function setView(array $view = null) : Worksheet{
		$this->view = $view;
		return $this;
	}

	/**
	 * @see Worksheet::setPageSetup() Параметры pageSetup
	 *
	 * @return array|null - параметры печати
	 */
	function getPageSetup() : array{
		return $this->pageSetup;
	}

	/**
	 * Параметры печати
	 *
	 * $pageSetup
	 * 		['margins']				array Пробелы на границах страницы (в дюймах)
	 * 			['left']			float
	 * 			['right']			float
	 * 			['top']				float
	 * 			['bottom']			float
	 * 			['header']			float
	 * 			['footer']			float
	 * 		['orientation']			string Ориентация страницы ('portrait', 'landscape')
	 * 		['horizontalDpi']		int Точек на дюйм по горизонтали
	 * 		['verticalDpi']			int Точек на дюйм по вертикали
	 * 		['pageOrder']			string Порядок печати страниц ('downThenOver', 'overThenDown')
	 * 		['blackAndWhite']		bool Печать без цвета
	 * 		['draft']				bool Печать с меньшим качеством (и чернилами)
	 * 		['cellComments']		string Где разместить комментарии ('atEnd', 'asDisplayed', 'None')
	 * 		['errors']				string Где показывать ошибки ('dash', 'blank', 'NA', 'displayed')
	 * 		['scale']				int Процент увеличения/уменьшения размеров печати
	 * 		['fitToWidth']			int Сколько страниц должно помещаться на листе по ширине (активно если нет scale)
	 * 		['fitToHeight']			int Сколько страниц должно помещаться на листе по высоте (активно если нет scale)
	 * 		['paperSize']			int Какой размер бумаги использовать (9 - А4)
	 * 		['showRowColHeaders']	bool Показывать номера строк и столбцов
	 * 		['firstPageNumber']		int Какой номер использовать для первой страницы
	 *
	 * @param array $pageSetup - свойства, влияющие на печать таблицы
	 * @return Worksheet - $this
	 */
	function setPageSetup(array $pageSetup) : Worksheet{
		$this->pageSetup = array_merge($this->pageSetup, $pageSetup);

		$this->pageSetup['fitToPage'] = (bool) (
			$this->pageSetup
			&& (($this->pageSetup['fitToWidth'] ?? false)
				|| ($this->pageSetup['fitToHeight'] ?? false))
			&& !($this->pageSetup['scale'] ?? false)
		);

		return $this;
	}

	/**
	 * @return string|null - автоматический фильтр
	 */
	function getAutoFilter(){
		return $this->autoFilter;
	}

	/**
	 * @param string|null $autoFilter - автоматический фильтр ('A1:A5')
	 * @return Worksheet - $this
	 */
	function setAutoFilter(string $autoFilter = null) : Worksheet{
		$this->autoFilter = $autoFilter;
		return $this;
	}

	/**
	 * @return bool - зафиксированы ли изменения
	 */
	function isCommitted() : bool{
		return $this->committed;
	}

	/**
	 * @return string - путь к временному файлу таблицы
	 */
	function getFilename() : string{
		return $this->filename;
	}

	/**
	 * @return string - путь к файлу таблицы внутри xlsx
	 */
	function getLocalname() : string{
		return 'xl/worksheets/sheet' . $this->id . '.xml';
	}

	/**
	 * @return string - id связи файла таблицы
	 */
	function getRId() : string{
		return $this->rId ?? '';
	}

	/**
	 * @param string $rId - id связи файла таблицы
	 * @return Worksheet - $this
	 */
	function setRId(string $rId) : Worksheet{
		$this->rId = $rId;
		return $this;
	}

	/**
	 * @return SheetRels - гиперссылки внутри таблицы
	 */
	function getSheetRels() : SheetRels{
		return $this->sheetRels;
	}

	/**
	 * @param array|null $values - значения ячеек строки
	 * @return Row - строка таблицы
	 */
	function addRow(array $values = null) : Row{
		$row = new Row($this, count($this->rows) + $this->lastUncommittedRow);
		if (!is_null($values)) $row->setValues($values);

		$this->rows[] = $row;

		return $row;
	}

	/**
	 *	Зафиксировать файл таблицы.
	 */
	function commit(){
		if ($this->isCommitted()) return;
		$this->committed = true;

		foreach ($this->rows as $row) (new RowXml())->render($this->xml, $row->genModel());
		unset($this->rows);

		$this->endWorksheet();

		$this->xml->flush();
		unset($this->xml);

		$this->sheetRels->commit();
	}

	/**
	 * Зафиксировать строки таблицы.
	 *
	 * @param Row $lastRow - последняя фиксируемая строка
	 */
	function commitRow(Row $lastRow){
		if ($this->isCommitted()) return;

		$rowXml = new RowXml();

		$found = false;
		while (count($this->rows) && !$found) {
			$row = array_shift($this->rows);
			$found = (bool) ($row->getNumber() == $lastRow->getNumber());

			$rowXml->render($this->xml, $row->getModel());
			$this->lastUncommittedRow++;
		}
	}

	/**
	 * @return array - модель таблицы
	 */
	function getModel() : array{
		return [
			'id' => $this->id,
			'name' => $this->name,
			'rId' => $this->rId ?? '',
			'partName' => $this->getLocalname()
		];
	}

	/**
	 *	Начать файл таблицы.
	 */
	private function startWorksheet(){
		$this->xml->startDocument('1.0', 'UTF-8', 'yes');
		$this->xml->startElement('worksheet');

		$this->xml->writeAttribute('xmlns', 'http://schemas.openxmlformats.org/spreadsheetml/2006/main');
		$this->xml->writeAttribute('xmlns:r', 'http://schemas.openxmlformats.org/officeDocument/2006/relationships');
		$this->xml->writeAttribute('xmlns:mc', 'http://schemas.openxmlformats.org/markup-compatibility/2006');
		$this->xml->writeAttribute('mc:Ignorable', 'x14ac');
		$this->xml->writeAttribute('xmlns:x14ac', 'http://schemas.microsoft.com/office/spreadsheetml/2009/9/ac');

		(new SheetPropertiesXml())->render($this->xml, [
			'tabColor' => $this->tabColor,
			'pageSetup' => $this->pageSetup
		]);

		(new SheetFormatPropertiesXml())->render($this->xml, [
			'defaultRowHeight' => $this->defaultRowHeight,
			'outlineLevelCol' => $this->outlineLevelCol,
			'outlineLevelRow' => $this->outlineLevelRow,
			'dyDescent' => Worksheet::DY_DESCENT
		]);

		$this->writeColumns();

		$this->xml->startElement('sheetData');
	}

	/**
	 *	Закончить файл таблицы.
	 */
	private function endWorksheet(){
		$this->xml->endElement();

		// AutoFilter
		// MergeCells

		(new ListXml('hyperlinks', new HyperlinkXml()))->render($this->xml, $this->sheetRels->getHyperlinks());
		(new PageMargins())->render($this->xml, $this->pageSetup['margins'] ?? null);
		(new PageSetupXml())->render($this->xml, $this->pageSetup);

		$this->xml->endElement();
		$this->xml->endDocument();
	}

	private function writeColumns(){
		// Реализовать
	}
}