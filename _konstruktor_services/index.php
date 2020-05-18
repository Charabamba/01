<?
//require_once $_SERVER['DOCUMENT_ROOT'] . '/bitrix/modules/main/include/prolog_before.php';
require_once $_SERVER["DOCUMENT_ROOT"] . '/bitrix/header.php';
$baza_arr = unserialize(file_get_contents($_SERVER["DOCUMENT_ROOT"] . "/include/konstruktor_module/baza.txt"));
//CModule::IncludeModule("iblock");
\Bitrix\Main\Loader::includeModule('iblock');
$arSelect = array();
$arElementOutput = array();

$url = $_SERVER['REQUEST_URI'];
if($_SERVER['QUERY_STRING']) {
	$get = $_SERVER['QUERY_STRING'];
    $url = str_ireplace('?' . $get, '', $url);
}
$razbien_array = explode("/", $url);
//echo "<pre>"; print_r($razbien_array); echo "</pre>";

// для построения навиг. цепочек
for($i = 2; $i < count($razbien_array) - 1; $i++) {
	$chain_arr[$i] = '/';
	for($j = 1; $j <= $i; $j++) {
		$chain_arr[$i] .= $razbien_array[$j] . '/';
	}
}
//echo "<pre>chain_arr: "; print_r($chain_arr); echo "</pre>";

// определяем
for($i = 0; $i < count($baza_arr['IB_1']); $i++) {
	if($baza_arr['IB_1'][$i][1] == $razbien_array[1]) {
		$ib_id = $baza_arr['IB_1'][$i][0];
		$max_level = $baza_arr['IB_1'][$i][4];
		$ib_name = $baza_arr['IB_1'][$i][3];
		continue;
	}
}


// если элемент, то в карточку
$id_element = CIBlockFindTools::GetElementID(false, $razbien_array[count($razbien_array) - 2], false, false, array('IBLOCK_CODE' => $razbien_array[1]));
if($id_element > 0) {
	$arSelect = array('ID', 'IBLOCK_ID');
	foreach($baza_arr[settings_sections_ib][fields_element][$ib_id][2] as $key => $value) {
		if($value == 'on') {
			$arSelect[] = $key;
		}
	}
	foreach($baza_arr[settings_sections_ib][propertyes_element][$ib_id][2] as $key => $value) {
		if($value == 'on') {
			$arSelect[] = 'PROPERTY_' . strtoupper($key);
		}
	}

	$arSelectChunk = array_chunk($arSelect, 50); // разбиваю чтобы не было  [[1116] Too many tables; MySQL can only use 61 tables in a join]
	//echo "<pre>arSelectChunk: "; print_r($arSelectChunk); echo "</pre>";

	foreach($arSelectChunk as $value) {
		$arFilter = array('IBLOCK_ID' => $ib_id, "IBLOCK_CODE" => $razbien_array[1], "ACTIVE" => "Y", "CODE" => $razbien_array[count($razbien_array) - 2], 'ID' => $id_element);
		$CIBlockResultObject = CIBlockElement::GetList(array('SORT' => 'ASC'), $arFilter, false, array(), $value);
		while($arElement = $CIBlockResultObject->Fetch()) {
			$arElementOutput = array_merge($arElementOutput, $arElement);
		}
	}
	//echo "<pre>ЭЛЕМЕНТ (arElementOutput): "; print_r($arElementOutput); echo "</pre>";
	require $_SERVER['DOCUMENT_ROOT'] . '/' . $razbien_array[1] . '/' . 'template_element.php';
	require_once $_SERVER["DOCUMENT_ROOT"] . '/bitrix/footer.php';
	die();
}


// если раздел нулевого или более порядка
$id_section = (count($razbien_array)==3)?0:CIBlockFindTools::GetSectionID(false, $razbien_array[count($razbien_array) - 2], array('IBLOCK_CODE' => $razbien_array[1]));
$page_type = (count($razbien_array)==3)?0:1;
if($id_section >= 0) {
	// раздел
	if($id_section > 0) {
		$arFilter = array('IBLOCK_ID' => $ib_id, 'IBLOCK_CODE' => $razbien_array[1], "ACTIVE" => "Y", "CODE" => $razbien_array[count($razbien_array) - 2], 'ID' => $id_section);
		$arSection = CIBlockSection::GetList(array('SORT' => 'ASC'), $arFilter, false, array('NAME', 'PICTURE', 'DESCRIPTION', 'UF_*'))->Fetch();
		//echo '<pre>РАЗДЕЛ (arSection):' . $razbien_array[count($razbien_array) - 2] . ' '; print_r($arSection); echo '</pre>';
	}
	
	// подразделы раздела
	$arFilter = array('IBLOCK_ID' => $ib_id, 'IBLOCK_CODE' => $razbien_array[1], "ACTIVE" => "Y", 'SECTION_ID' => $id_section);
	$CIBlockResultObject = CIBlockSection::GetList(array('SORT' => 'ASC'), $arFilter, false, array('ID', 'CODE', 'NAME', 'PICTURE', 'DESCRIPTION', 'UF_*'));
	while($arItem = $CIBlockResultObject->Fetch()){
		$arSections[] = $arItem;
	}
	//echo "<pre>ПОДРАЗДЕЛЫ РАЗДЕЛА (arSections): "; print_r($arSections); echo "</pre>";
	
	// элементы раздела
	if(count($baza_arr[settings_sections_ib][fields_element][$ib_id][$page_type]) > 0 || count($baza_arr[settings_sections_ib][propertyes_element][$ib_id][$page_type]) > 0) {	
		$arFilter = array('IBLOCK_ID' => $ib_id, 'IBLOCK_CODE' => $razbien_array[1], "ACTIVE" => "Y", 'SECTION_ID' => $id_section);
		$arSelect = array('ID', 'IBLOCK_ID');
		foreach($baza_arr[settings_sections_ib][fields_element][$ib_id][$page_type] as $key => $value) {
			if($value == 'on') {
				$arSelect[] = $key;
			}
		}
		foreach($baza_arr[settings_sections_ib][propertyes_element][$ib_id][$page_type] as $key => $value) {
			if($value == 'on') {
				$arSelect[] = 'PROPERTY_' . strtoupper($key);
			}
		}
		//echo "<pre>arSelect: "; print_r($arSelect); echo "</pre>";
		$CIBlockResultObject = CIBlockElement::GetList(array('SORT' => 'ASC'), $arFilter, false, array(), $arSelect);
		while($arSectionElement = $CIBlockResultObject->Fetch()) {
			$arSectionElements[] = $arSectionElement;
		}
		//echo "<pre>ЭЛЕМЕНТЫ РАЗДЕЛА (arSectionElements): "; print_r($arSectionElements); echo "</pre>";
	}
	
	// элементы подразделов
	
	if($id_section == 0)
		require $_SERVER['DOCUMENT_ROOT'] . '/' . $razbien_array[1] . '/' . 'template_section_0.php';
	if($id_section > 0)
		require $_SERVER['DOCUMENT_ROOT'] . '/' . $razbien_array[1] . '/' . 'template_section.php';
	require_once $_SERVER["DOCUMENT_ROOT"] . '/bitrix/footer.php';
	die();
}









header('HTTP/1.1 301 Moved Permanently');
header('Location: http://' . $_SERVER["SERVER_NAME"]);