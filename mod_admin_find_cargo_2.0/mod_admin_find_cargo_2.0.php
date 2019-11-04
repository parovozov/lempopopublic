<?php
/**
 * @package		Joomla.Site
 * @subpackage	mod_login
 * @copyright	Copyright (C) 2005 - 2012 Open Source Matters, Inc. All rights reserved.
 * @license		GNU General Public License version 2 or later; see LICENSE.txt
 */

// no direct access
defined( '_JEXEC' )or die;
$document = JFactory::getDocument();
$db = JFactory::getDBO();
$user = JFactory::getUser();
$document->addStyleSheet( '/modules/mod_admin_find_cargo_2.0/css/mod_admin_find_cargo_2.0.css' );
$document->addStyleSheet( '/modules/mod_admin_find_cargo_2.0/css/style.css' );
$document->addScript( '/modules/mod_admin_find_cargo_2.0/js/mod_admin_find_cargo_2.0.js' );
$document->addScript( 'modules/mod_admin_find_cargo_2.0/js/will_pickdate.js' );
$document->addScript( 'modules/mod_admin_find_cargo_2.0/js/jquery.mousewheel.js' );
require_once( "templates/mapservers/clas_tpl/class_pagination.php" );
require_once( "templates/mapservers/clas_tpl/class_total.php" );
$rand = new RandSymbols();
$jconfig = new JConfig();
$mysqli = new mysqli( "localhost", $jconfig->user, $jconfig->password, $jconfig->db, '3306' );

$query = "SELECT id_company, params FROM #__users WHERE id={$user->id} LIMIT 1";
$db->setQuery( $query );
$result = $db->loadRow();
$curent_id = $rand->getcodeid( $user->id );
$curent_id_firm = $result[0];
$masobj = json_decode( $result[1], true );
if(!empty($curent_id_firm))
{
	$curent_id_firm= $rand->getcodeid( $curent_id_firm );
	$favor_bd_pol="favor.id_user";
}
else {
	$curent_id_firm= $rand->getcodeid( $user->id );
	$favor_bd_pol="favor.id_firm";
}

//тип кузова
function cartype( $type, $mascartypes ) {
	foreach ( $mascartypes as $val ) {
		if ( $val[ 'id' ] == $type ) {
			$s = $val[ 'cartypes' ];
			break;
		}
	}
	if ( !isset( $s ) ) {
		$s = $mascartypes[ 1 ][ 'cartypes' ];
	}
	return ( $s );
}

//тип кузова
$mascartypes = array();
$cartypecheckbox = "";
$query = "SELECT * FROM #__cartypes";
$db->setQuery( $query );
$result = $db->loadAssocList();
foreach ( $result as $v ) {
	array_push( $mascartypes, $v );
	$ct = trim( str_replace( ".", "", $v[ 'cartypes' ] ) );
	if ( isset( $_GET[ 'typecargo' ] ) && !isset( $_GET[ 'reset' ] ) )
		$ch = ( in_array( $v[ 'id' ], $_GET[ 'typecargo' ] ) ) ? "checked" : "";
	else $ch = "";
	$active = ( !empty( $ch ) ) ? "class='activ'" : "";
	$cartypecheckbox .= "<label {$active}><input type='checkbox' name='typecargo[]' value='{$v['id']}' {$ch}/>{$ct}</label>";
}

// показываем или скрываем фильтр
if ( isset( $masobj[ "filterhideshow" ] ) && $masobj[ "filterhideshow" ] == 0 ) {
	$arrowhideshow = "<div class='text2' style='display:block';>Фильтр<div class='strelkadown'></div></div>";
	$arrowclas = "style='display:none';";
} else {
	$arrowhideshow = "";
	$arrowclas = "";
}
$fromcity = "";
$tocity = "";
$datestart = "0";
$datestop = "0";
$value = "";
$mass = "";
//выборка когда ничего не указано в качестве параметров отбора
if ( !isset( $_GET[ 'start' ] ) && !isset( $_GET[ 'stop' ] ) && !isset( $_GET[ 'apply' ] ) ) {

	$query = "SELECT COUNT(*) FROM #__sender_active delive LIMIT 1000";
	$db->setQuery( $query );
	$count_page = $db->loadResult();
	//$limit — количество записей на страницу
	//$count_all — общее количество записей
	//$page_num — номер страницы на которой находится пользователь
	$uri = JFactory::getURI();
	$url = $uri->toString( array( 'scheme', 'host', 'path', 'query' ) );
	$limit = 20;
	$count_all = $count_page;
	$page_num = ( isset( $_GET[ 'page' ] ) ) ? $_GET[ 'page' ] : 1;
	$navi = new PaginateNavigationBuilder( $url );
	$navi->spread = 4;
	$paginator = $navi->build( $limit, $count_all, $page_num );
	$count_pages = $navi->countpages; // количество страниц
	$startdb = $navi->startdb; // элемент с которого начинается выборка из бд
	

	$query1 = "
	SET @road:=-1, @user:=-1, @first:=-1, @second:=-1, @result=0, @count=0;
	CREATE TEMPORARY TABLE temp_{$user->id}(	
		SELECT delive.id, delive.idroad, delive.iduser, 
		IF( @road<>idroad, @first:=0, @first:=1) first, 
		IF(@user<>iduser, @second:=0, @second:=1) second, 
		@road:=idroad road_var,
		@user:=iduser user_var,
		IF(@first=1 AND @second=1, @result, @result:=@result+1) result,
		@count:=@count+1 count_row
		FROM {$jconfig->dbprefix}sender_active delive		
		ORDER BY result DESC, id ASC LIMIT {$startdb}, {$limit}		
		);		
		SELECT tmp.id, tmp.idroad, tmp.iduser, tmp.result,		
		delive.adresstart, delive.adresstop, delive.delivestartx, delive.delivestarty, delive.delivestopx, delive.delivestopy, delive.datestart, delive.datestop, delive.length, delive.freeshape, delive.weight, delive.comment, delive.cartype,	delive.packtype, delive.cargotype, 
		usr.id_company, usr.name, usr.fio, usr.email, usr.mobilnik, usr.telefon, usr.whap_vib, usr.web, usr.avatarka, usr.information, usr.likeup,  usr.likedown,
		t.type,
		favor.id favorid,
		pack.packtype pkt
		FROM temp_{$user->id} tmp
		LEFT JOIN {$jconfig->dbprefix}sender_active delive ON (delive.id=tmp.id)
		LEFT JOIN {$jconfig->dbprefix}users usr ON (usr.id=tmp.iduser)
		LEFT JOIN {$jconfig->dbprefix}usertype t ON (t.id=usr.usertype1)
		LEFT JOIN {$jconfig->dbprefix}favorit_firm favor ON ({$favor_bd_pol}='{$user->id}' AND (favor.favorit_id_firm=usr.id OR favor.favorit_id_firm=usr.id_company))
		LEFT JOIN {$jconfig->dbprefix}packtype pack ON (pack.id=delive.packtype)
		ORDER BY tmp.result DESC, tmp.id ASC
	";
} 
elseif ( isset( $_GET[ 'start' ] ) && isset( $_GET[ 'stop' ] ) && !isset( $_GET[ 'apply' ] ) && !isset( $_GET[ 'reset' ] ) ) {
	$fromcity = htmlspecialchars( urldecode( $_GET[ 'start' ] ) );
	$tocity = htmlspecialchars( urldecode( $_GET[ 'stop' ] ) );
	$subquerystart = "delive.adresstart LIKE '%{$fromcity}%'";
	$subquerystop = "delive.adresstop LIKE '%{$tocity}%'";

}
elseif ( isset( $_GET[ 'apply' ] ) &&  !empty($_GET[ 'fromcity' ]) && !empty($_GET[ 'tocity' ])) {
	$subquerystart = "";
	$subquerystop = "";

	if ( !empty( $_GET[ 'value' ] ) ) {
		$value = htmlspecialchars( $_GET[ 'value' ] );
		if ( empty( $subquerystart ) )$subquerystart .= "delive.freeshape >={$value}";
		else $subquerystart .= " AND delive.freeshape >= {$value}";

		if ( empty( $subquerystop ) )$subquerystop .= "delive.freeshape >={$value}";
		else $subquerystop .= " AND delive.freeshape >= {$value}";
	}
	if ( !empty( $_GET[ 'mass' ] ) ) {
		$mass = htmlspecialchars( $_GET[ 'mass' ] );
		$mass_forbd=round($mass);
		$mass_forbd=$mass_forbd/1000;
		if ( empty( $subquerystart ) )$subquerystart .= "delive.weight >={$mass_forbd}";
		else $subquerystart .= " AND delive.weight >= {$mass_forbd}";

		if ( empty( $subquerystop ) )$subquerystop .= "delive.weight >={$mass_forbd}";
		else $subquerystop .= " AND delive.weight >= {$mass_forbd}";
	}
	if ( !empty( $_GET[ 'fromcity' ] ) ) {
		$fromcity = htmlspecialchars( $_GET[ 'fromcity' ] );
		if ( empty( $subquerystart ) )$subquerystart .= "delive.adresstart LIKE '%{$fromcity}%'";
		else $subquerystart .= " AND delive.adresstart LIKE '%{$fromcity}%'";
	}
	if ( !empty( $_GET[ 'tocity' ] ) ) {
		$tocity = htmlspecialchars( $_GET[ 'tocity' ] );
		if ( empty( $subquerystop ) )$subquerystop .= "delive.adresstop LIKE '%{$tocity}%'";
		else $subquerystop .= " AND delive.adresstop LIKE '%{$tocity}%'";
	}
	if ( !empty( $_GET[ 'datestart' ] ) ) {
		$datestart = htmlspecialchars( $_GET[ 'datestart' ] );
		$datestart = date( "Y-m-d  H:i:s", strtotime( $datestart ) );
		if ( empty( $subquerystart ) )$subquerystart .= "delive.datestart >= '{$datestart}'";
		else $subquerystart .= " AND delive.datestart >= '{$datestart}'";
	}
	if ( !empty( $_GET[ 'datestop' ] ) ) {
		$datestop = htmlspecialchars( $_GET[ 'datestop' ] );
		$datestop = date( "Y-m-d  H:i:s", strtotime( $datestop ) );
		if ( empty( $subquerystop ) )$subquerystop .= "delive.datestop <= '{$datestop}'";
		else $subquerystop .= " AND delive.datestop <= '{$datestop}'";
	}
	if ( isset( $_GET[ 'typecargo' ] ) ) {
		$querycartype = "";
		foreach ( $_GET[ 'typecargo' ] as $v ) {
			$v = htmlspecialchars( $v );
			$querycartype .= ( empty( $querycartype ) ) ? "delive.cartype LIKE '%\_{$v}\_%'" : " OR delive.cartype LIKE '%\_{$v}\_%'";
		}
		if ( empty( $subquerystart ) )$subquerystart .= " (" . $querycartype . ")";
		else $subquerystart .= " AND (" . $querycartype . ")";

		if ( empty( $subquerystop ) )$subquerystop .= " (" . $querycartype . ")";
		else $subquerystop .= " AND (" . $querycartype . ")";
	}

}
else
{
	$subquery="";
	if ( !empty( $_GET[ 'value' ] ) && !empty( $subquery ) ) {
		$value = htmlspecialchars( $_GET[ 'value' ] );
		$subquery .= " AND delive.freeshape >= {$value}";
	}elseif ( !empty( $_GET[ 'value' ] ) ) {
		$value = htmlspecialchars( $_GET[ 'value' ] );
		$subquery .= "delive.freeshape >={$value}";
	}
	else{}
	if ( !empty( $_GET[ 'mass' ] ) && !empty( $subquery ) ) {
		$mass = htmlspecialchars( $_GET[ 'mass' ] );
		$mass_forbd=round($mass);
		$mass_forbd=$mass_forbd/1000;
		$subquery .= " AND delive.weight >= {$mass_forbd}";
	}elseif ( !empty( $_GET[ 'mass' ] ) ) {
		$mass = htmlspecialchars( $_GET[ 'mass' ] );
		$mass_forbd=round($mass);
		$mass_forbd=$mass_forbd/1000;
		$subquery .= "delive.weight >={$mass_forbd}";
	}
	else{}
	if ( !empty( $_GET[ 'fromcity' ] ) && !empty( $subquery ) ) {
		$fromcity = htmlspecialchars( $_GET[ 'fromcity' ] );
		$subquery .= " AND delive.adresstart LIKE '%{$fromcity}%'";
	} elseif ( !empty( $_GET[ 'fromcity' ] ) ) {
		$fromcity = htmlspecialchars( $_GET[ 'fromcity' ] );
		$subquery .= "delive.adresstart LIKE '%{$fromcity}%'";
	}
	else{}
	if ( !empty( $_GET[ 'tocity' ] ) && !empty( $subquery ) ) {
		$tocity = htmlspecialchars( $_GET[ 'tocity' ] );
		$subquery .= " AND delive.adresstop LIKE '%{$tocity}%'";
	} elseif ( !empty( $_GET[ 'tocity' ] ) ) {
		$tocity = htmlspecialchars( $_GET[ 'tocity' ] );
		$subquery .= "delive.adresstop LIKE '%{$tocity}%'";
	}
	else{}
	if ( !empty( $_GET[ 'datestart' ] ) && !empty( $subquery ) ) {
		$datestart = htmlspecialchars( $_GET[ 'datestart' ] );
		$datestart=date("Y-m-d  H:i:s", strtotime($datestart));
		$subquery .= " AND delive.datestart >= '{$datestart}'";
	}elseif ( !empty( $_GET[ 'datestart' ] ) ) {
		$datestart = htmlspecialchars( $_GET[ 'datestart' ] );
		$datestart=date("Y-m-d  H:i:s", strtotime($datestart));
		$subquery .= "delive.datestart >= '{$datestart}'";
	}
	else{}
	if ( !empty( $_GET[ 'datestop' ] ) && !empty( $subquery ) ) {
		$datestop = htmlspecialchars( $_GET[ 'datestop' ] );
		$datestop=date("Y-m-d H:i:s", strtotime($datestop));
		$subquery .= " AND delive.datestop <= '{$datestop}'";
	}elseif ( !empty( $_GET[ 'datestop' ] ) ) {
		$datestop = htmlspecialchars( $_GET[ 'datestop' ] );
		$datestop=date("Y-m-d  H:i:s", strtotime($datestop));
		$subquery .= "delive.datestop <= '{$datestop}'";
	}
	else{}
	if ( isset( $_GET[ 'typecargo' ] ) ) {
		$querycartype = "";
		foreach ( $_GET[ 'typecargo' ] as $v ) {
			$querycartype .= ( empty( $querycartype ) ) ? "delive.cartype LIKE '%\_{$v}\_%'" : " OR delive.cartype LIKE '%\_{$v}\_%'";
		}
		if ( !empty( $subquery ) )$subquery .= " AND (" . $querycartype . ")";
		else $subquery .= "(" . $querycartype . ")";
	}

	$query = "SELECT COUNT(*) FROM #__sender_active delive WHERE {$subquery}";
	$db->setQuery( $query );
	$result = $db->loadResult();
	$uri = JFactory::getURI();
	$url = $uri->toString( array( 'scheme', 'host', 'path', 'query' ) );
	$limit = 20;
	$count_all = $result;
	$page_num = ( isset( $_GET[ 'page' ] ) ) ? $_GET[ 'page' ] : 1;
	$navi = new PaginateNavigationBuilder( $url );
	$navi->spread = 4;
	$paginator = $navi->build( $limit, $count_all, $page_num );
	$count_pages = $navi->countpages; // количество страниц
	$startdb = $navi->startdb; // элемент с которого начинается выборка из бд

	$query3 = "SELECT 
delive.iddeliver, delive.idroad, delive.iduser, delive.adresstart, delive.adresstop, delive.delivestartx, delive.delivestarty, 
delive.delivestopx, delive.delivestopy, delive.datestart dstartorigin,  DATE_FORMAT(delive.datestart, '%d.%m.%Y %H:%i') datestart, delive.datestop dstoporigin, DATE_FORMAT(delive.datestop, '%d.%m.%Y %H:%i') datestop, delive.constantroad, delive.length, delive.freeshape, delive.weight, delive.comment, delive.cartype, delive.packtype, delive.cargotype, 
usr.id, usr.id_company, usr.name, usr.fio, usr.email, usr.mobilnik, usr.telefon, usr.whap_vib, usr.web, usr.avatarka,  usr.information, usr.likeup,  usr.likedown,
t.type,
favor.id favorid,
pack.packtype pkt
FROM #__sender_active delive
LEFT JOIN #__users usr ON (usr.id=delive.iduser)
LEFT JOIN #__usertype t ON (t.id=usr.usertype1)
LEFT JOIN #__favorit_firm favor ON ({$favor_bd_pol}='{$user->id}' AND (favor.favorit_id_firm=usr.id OR favor.favorit_id_firm=usr.id_company))
LEFT JOIN #__packtype pack ON (pack.id=delive.packtype)
WHERE {$subquery}
ORDER BY delive.id DESC
LIMIT {$startdb}, {$limit}
";
}

//если была отправлена форма или перешли по ссылке маршрута
if ( isset( $subquerystart ) && isset( $subquerystop ) ) {
	//количество записей для пагинации
	$query = "
	SET @road:=-1, @user:=-1, @first:=-1, @second:=-1, @result=0, @usl_1=0, @usl_2=0, @result_2=0, @idstart=-1, @idstop=-1;
	CREATE TEMPORARY TABLE temp_{$user->id}(
	SELECT delive.id, delive.idroad, delive.iduser, delive.cartype,
	IF( @road<>idroad, @first:=0, @first:=1) first, 
	IF(@user<>iduser, @second:=0, @second:=1) second, 
	@road:=idroad road_var, 
	@user:=iduser user_var, 
	IF(@first=1 AND @second=1, @result, @result:=@result+1) result, 
	IF(@first=1 AND @second=1, @usl_1, @usl_1:=0) usl_1, 
	IF(@first=1 AND @second=1, @usl_2, @usl_2:=0) usl_2, 
	
	IF({$subquerystart}, @usl_1:=1, @usl_1) usl_11, 
	IF(@usl_1=1 AND {$subquerystop}, @usl_2:=1, @usl_2) usl_22, 
	
	IF(@usl_1=1 AND @usl_2=1, @result_2:=1, @result_2:=0) result_2,
	IF(@first=1 AND @second=1, @idstart, @idstart:=delive.id) idstart, 
	IF(@result_2=1, @idstop:=delive.id, @idstop:=-1) idstop
	FROM {$jconfig->dbprefix}sender_active delive
	);
    SET @pre:=-1, @cou=0, @var1=-1;
	SELECT result_2,
    IF(result<>@pre, @var1:=1, @var1:=-0) var1,
    @pre:=result pre,
    IF( @var1 = 1, @cou:= @cou + 1, @cou )cou
    FROM temp_{$user->id} WHERE result_2 = 1 ORDER BY cou DESC LIMIT 1 ";

	if ( $mysqli->multi_query( $query ) ) {
		$mysqli->next_result();
		$mysqli->next_result();
		$mysqli->next_result();
		if ( $result = $mysqli->store_result() ) {
			$row = $result->fetch_assoc();
			$count_page = $row[ 'cou' ];
			$result->free();
		}
	}
	$uri = JFactory::getURI();
	$url = $uri->toString( array( 'scheme', 'host', 'path', 'query' ) );
	$limit = 20;
	$count_all = $count_page;
	$page_num = ( isset( $_GET[ 'page' ] ) ) ? $_GET[ 'page' ] : 1;
	$navi = new PaginateNavigationBuilder( $url );
	$navi->spread = 4;
	$paginator = $navi->build( $limit, $count_all, $page_num );
	$count_pages = $navi->countpages; // количество страниц
	$startdb = $navi->startdb; // элемент с которого начинается выборка из бд
	
	$query = "SELECT idstart, idstop, MAX(idstop) maxid FROM temp_{$user->id} WHERE result_2=1 GROUP BY result ORDER BY result DESC, id ASC LIMIT {$startdb}, {$limit}	
	";
 
	if ( $mysqli->multi_query( $query ) ) {
		$query2 = "";
		if ( $result = $mysqli->store_result() ) {
			while ( $row = $result->fetch_assoc() ) {
				$query2 .= "
				SELECT
				delive.id, delive.idroad, delive.iduser, 
				delive.adresstart, delive.adresstop, delive.delivestartx, delive.delivestarty, delive.delivestopx, delive.delivestopy, delive.datestart, delive.datestop, delive.length, delive.freeshape,
				delive.weight, delive.comment, delive.cartype, delive.packtype, delive.cargotype,
				usr.id usrid, usr.id_company, usr.name, usr.fio, usr.email, usr.mobilnik, usr.telefon, usr.whap_vib, usr.web, usr.avatarka, usr.information, usr.likeup,  usr.likedown,
				t.type,
				favor.id favorid,
				pack.packtype pkt
				FROM  {$jconfig->dbprefix}sender_active delive
				LEFT JOIN {$jconfig->dbprefix}users usr ON (usr.id=delive.iduser)
				LEFT JOIN {$jconfig->dbprefix}usertype t ON (t.id=usr.usertype1)
				LEFT JOIN {$jconfig->dbprefix}favorit_firm favor ON ({$favor_bd_pol}='{$user->id}' AND (favor.favorit_id_firm=usr.id OR favor.favorit_id_firm=usr.id_company))
				LEFT JOIN {$jconfig->dbprefix}packtype pack ON (pack.id=delive.packtype)
				WHERE delive.id>={$row['idstart']} AND delive.id<={$row['maxid']} ORDER BY delive.id;";
			}
			$result->free();

		}
	}
}

if(isset($_GET[ 'datestart' ])) $datestart=$_GET[ 'datestart' ]; else $datestart="";
if(isset($_GET[ 'datestop' ])) $datestop=$_GET[ 'datestop' ]; else $datestop="";

echo "<form  method='get' name='formfilter'>
	<div class='tipblock'>{$arrowhideshow}
	<div class='tipblock2' {$arrowclas}>
	<div class='text'>Фильтр<div class='strelkaup'></div></div>
	<div class='block1 st1'>Из <input type='text' class='city_start' placeholder='Город отправления' autocomplete='off' name='fromcity' maxlength='255' value='{$fromcity}'></div>
	<div class='block1 st2'>В <input type='text' class='city_stop' placeholder='Город назначения' autocomplete='off' name='tocity' maxlength='255' value='{$tocity}'></div>
	<div class='block1'>Дата старт <input type='text' class='date1 datestart' value='{$datestart}' autocomplete='off'  placeholder='календарь' name='datestart' maxlength='255'></div>
	<div class='block1'>Дата стоп <input type='text' class='date1 datestop' value='{$datestop}' autocomplete='off'  placeholder='календарь' name='datestop' maxlength='255'></div>
	<div class='block1'>Объем от <input type='text' class='textvalue' placeholder='куб. м' name='value' maxlength='10' value='{$value}'></div>
	<div class='block1'>Масса от <input type='text' class='textmass' placeholder='кг' name='mass' maxlength='10' value='{$mass}'></div>
	<div class='block2'><span>Тип кузова</span> {$cartypecheckbox}</div>
	<div class='divsubmit'>
	<input type='submit' id='formsubmit' value='Применить' name='apply'>
	<input type='submit' id='formreset' value='Сбросить' name='reset'>
	</div>
	</div></div></form>";

function getweight($rowweight){
	$weight=0;
	if ( !empty( $rowweight ) && $rowweight < 1 ) {
		$weight = $rowweight * 1000;
		$weight = "<span>".$weight."</span>";
		$weight .= " кг";}
	else{
		$weight = round( $rowweight, 1 );
		$weight = "<span>".$weight."</span>";
		$weight .= " т";}
	return $weight;
}
function getcartype($rowcartype, $mascartypes){
	$cartype = "";
	$masexplode = explode( "_", $rowcartype );
	$count = count( $masexplode );
	if ( !empty( $masexplode ) && count( $count > 1 ) ) {
		for ( $ii = 1; $ii < $count - 1; $ii++ ) {
			if ( $ii == $count - 2 )
				$cartype .= str_replace( ".", "", cartype( $masexplode[ $ii ], $mascartypes ) );
			else
				$cartype .= str_replace( ".", "", cartype( $masexplode[ $ii ], $mascartypes ) ) . " / ";
		}
	}
	return $cartype;
}
function getadress($rowadress){
	$masexplode = explode( ",", $rowadress );
	$ccc = count($masexplode);
	if($ccc>1) $adress  = trim($masexplode[$ccc-1]);
	else $adress  = trim($masexplode[0]);
	return $adress;
}
function geturl($adres){
	$pos1 = mb_strpos( $adres, "(" , 0, "UTF-8");
	if($pos1 !== false)
	{
		$pos2 = mb_strpos( $adres, ")" , 0, "UTF-8");
		if($pos2 !== false)
		{
			$substr1=mb_substr($adres, 0, $pos1, "UTF-8");
			$substr2=mb_substr($adres, $pos2+1, NULL, "UTF-8");
			$adres_return= trim($substr1.$substr2);
		}
		else $adres_return=trim($adres);
	}
	else $adres_return=trim($adres);	
	return $adres_return;
}

function BieldString( $row, $mascartypes, $rand ) {
	$str = "";
	$avatitle = "";
	$profilebiz = ( !empty( $row[ 'type' ] ) ) ? $row[ 'type' ] : 'Грузоперевозчик';
	$profilebiz_link = ( !empty( $row[ 'type' ] ) ) ? $row[ 'type' ] : '';
	if ( !empty( $row[ 'avatarka' ] ) ) {
		$avatar = JURI::base() . "images/avatar/useravatar/{$row['avatarka']}";
		$avatitle = " title='{$profilebiz} {$row['name']}'";
	} else
		$avatar = JURI::base() . "images/emptyavatargrey.png";

	if ( !empty( $row[ 'mobilnik' ] ) ) {
		$mobilnik = "<div class='telbox'><img src='" . JURI::base() . "images/telefon.png'/>";
		$masjsom = json_decode( $row[ 'whap_vib' ] );
		$mobilnik .= ( $masjsom[ 0 ][ 0 ] == 1 ) ? "<img src='" . JURI::base() . "images/whap.png'/>": "";
		$mobilnik .= ( $masjsom[ 0 ][ 1 ] == 1 ) ? "<img src='" . JURI::base() . "images/viber.png'/>": "";
		$mobilnik .= "<div>" . $row[ 'mobilnik' ] . "</div></div>";

	} else $mobilnik = "";

	if ( !empty( $row[ 'telefon' ] ) ) {
		$telefon = "<div class='telbox'><img src='" . JURI::base() . "images/telefon.png'/>";
		$masjsom = json_decode( $row[ 'whap_vib' ] );
		$telefon .= ( $masjsom[ 1 ][ 0 ] == 1 ) ? "<img src='" . JURI::base() . "images/whap.png'/>": "";
		$telefon .= ( $masjsom[ 1 ][ 1 ] == 1 ) ? "<img src='" . JURI::base() . "images/viber.png'/>": "";
		$telefon .= "<div>" . $row[ 'telefon' ] . "</div></div>";

	} else $telefon = "";

	$email = ( strpos( $row[ 'email' ], "No@No" ) !== false ) ? "" : "<div class='telbox'><img src='" . JURI::base() . "images/maillogot.png'/><div>" . $row[ 'email' ] . "</div></div>";
	$cartype=getcartype($row[ 'cartype' ], $mascartypes);
	$freeshape = ( !empty( $row[ 'freeshape' ] ) ) ? "<span>".round( $row[ 'freeshape' ], 2 )."</span> м. куб": "";	
	$weight = getweight($row[ 'weight' ]);	
	$adresstart=getadress($row[ 'adresstart' ]);
	$adresstop=getadress($row[ 'adresstop' ]);
	$urladres1=geturl($adresstart);
	$urladres2=geturl($adresstop);
	$url = "start=" . urlencode( $urladres1 )."&stop=" . urlencode( $urladres2 );
	$web = ( !empty( $row[ 'web' ] ) ) ? "<div class='telbox'><img src='" . JURI::base() . "images/ie.png'/><a href='{$row['web']}'>" . $row[ 'web' ] . "</a></div>": "";

	$leninfo=strlen($row[ 'information' ]);
	if ( !empty($row[ 'information' ]) && $leninfo>=55) {
		$pos1 = strpos( $row[ 'information' ], " ", 45 );
		if($pos1!==false) $info_img = trim(substr( $row[ 'information' ], 0, $pos1 )) . "...";
		else $info_img = trim($row[ 'information' ]);
		$info_img = "<br/><a class='support' tabindex='1'><span class='info_click'>i</span> <div>{$info_img}</div> <span class='info_tip'>".nl2br($row['information'])."</span></a>";
	} 
	elseif( !empty($row[ 'information' ]) && $leninfo==45) {
		$info_img = trim($row[ 'information' ]);
		$info_img = "<br/><a class='support' tabindex='1'><span class='info_click'>i</span> <div>{$info_img}</div> <span class='info_tip'>".nl2br($row['information'])."</span></a>";
	} 
	else $info_img = "";

	$userid = $rand->getcodeid( $row[ 'iduser' ] );
	$id_company = ( !empty( $row[ 'id_company' ] ) ) ? $rand->getcodeid( $row[ 'id_company' ] ) : $userid;
	$idroad= $rand->getcodeid( $row['idroad'] );

	if ( !empty( $row[ 'favorid' ] ) ) {
		$class_star = "delfavorites";
		$datatitle_star1 = "Удален из избранного";
		$datatitle_star2 = "Добавлен в избранное";
		$title_star = "Удалить грузоперевозчика из избранного";
	} else {
		$class_star = "addfavourites";
		$datatitle_star1 = "Добавлен в избранное";
		$datatitle_star2 = "Удален из избранного";
		$title_star = "Добавить грузоперевозчика в избранное";
	}
	$datestart = date( "d.m.Y H:i", strtotime( $row[ 'datestart' ] ) );
	$datestop = date( "d.m.Y H:i", strtotime( $row[ 'datestop' ] ) );
	$cargotype = ( !empty( $row['cargotype'] ) ) ? "<div class='itcargo'>".$row['cargotype']."</div>" : "";
	$packt = ( !empty( $row['pkt'] ) ) ? "<div class='itpkt'>".$row['pkt']."</div>" : "";
	$subcomment = ( !empty( $row['comment'] ) ) ? "<div class='itcom'>".nl2br($row[ 'comment' ])."</div>" : "";
	if($cargotype || $packt || $subcomment) $comment=$cargotype.$packt.$subcomment;
	else $comment="";
	$str .= "<div class='road_truck'>
		<div><img src='{$avatar}'{$avatitle}/><div class='likeup'><img src='" . JURI::base() . "images/likeup.png'/>{$row['likeup']}</div><div class='likedown'><img src='" . JURI::base() . "images/likedown.png'/>{$row['likedown']}</div></div>
		<div><a href='" . JURI::base() . "index.php/pasportlog?user={$userid}'>{$row['name']}</a><strong>{$profilebiz_link}</strong>{$mobilnik}{$telefon}{$email}{$web}{$row['fio']}{$info_img}</div>
		<div><img src='" . JURI::base() . "images/zvezdaadd.png' title='{$title_star}' data-title1='{$datatitle_star1}' data-title2='{$datatitle_star2}' class='{$class_star} {$id_company}'/></div>
		<div>{$cartype}<div>свободно</div>{$freeshape}</br>{$weight}</div>
		<div><a href='" . JURI::base() . "index.php/admin-truck?{$url}'>{$adresstart} - {$adresstop}</a><div>{$row['length']} км</div><time datetime='{$row['datestart']}'>{$datestart}</time> - <time datetime='{$row['datestop']}'>{$datestop}</time><img src='../../images/yandplace.png' class='opmap'></div>
		<div>{$comment}</div>
		<div><img src='" . JURI::base() . "images/addroadunloged.png' class='addfavouritesroad' data-title1='Заявка отправлена' data-title2='Заявка отменена' id='{$userid} {$id_company} {$idroad}'/></div>
		</div>";
	return $str;
}
$coordinats="";
$str = "";
$comment="";
$totaladres="";
$predatestop="";
$adress1=""; $adress2="";
$datestart2="";$datestop2="";
$datestartempty="";$datestopempty="";
$flagstart=0;$flagstop=0;$flagstartnow=0;$flagstopnow=0;
$length=0.0;
$firstlength=0;
$i=0;
//  если была отправлена форма или перешли по сслыке с адрсом
if ( isset( $query2 ) ) {
	if ( $mysqli->multi_query( $query2 ) ) {
		do { 
			if ( $result = $mysqli->store_result() ) {
				$firstnewloop=1;				
				while ( $row = $result->fetch_assoc() ) {
					$coordinats.=$row['delivestartx']*1.2."-".$row['delivestarty']*1.2."-".$row['delivestopx']*1.3."-".$row['delivestopy']*1.3." ";
					//формируем вывод из предыдущего прохода цикла
					//закрывает totfaladres последним адрес стоп
					if($firstnewloop && $totaladres)
					{
						$adresstop=getadress($preadrstop);						
						if(mb_stripos( $preadrstop,  $tocity)!==false)
						{
							$totaladres.="<div><span>".$adresstop."</div></span>";
							if(!$adress2) $adress2=$adresstop;
						}
						else $totaladres.="<div>".$adresstop."</div>";
						$totaladres="<div class='total_puth'>".$totaladres."</div>";
						
						if(!$adress1) $adress1=$adresempty1;
						if(!$adress2) $adress2=$adresempty2;
						$adress=$adress1." - ".$adress2;
						$urladres1=geturl($adress1);
						$urladres2=geturl($adress2);
						$url = "start=" . urlencode( $urladres1 )."&stop=" . urlencode( $urladres2 );						
						if(!$length)$length=$firstlength;					
						
						if(!$datestop2)//если в цикле всего один проход и дата стоп не установилась 
						{
							$datestop = date( "d.m.Y H:i", strtotime( $predatestop ) );
							$datestop2 = $predatestop;
						}
						if(!$datestart) {$datestart=$datestartempty; $datestart2=$datestartempty;}
						if(!$datestop) {$datestop=$datestopempty; $datestop2=$datestopempty;}
						
							
						
						$str .= "<div class='road_truck'>
						<div><img src='{$avatar}'{$avatitle}/><div class='likeup'><img src='" . JURI::base() . "images/likeup.png'/>{$likeup}</div><div class='likedown'><img src='" . JURI::base() . "images/likedown.png'/>{$likedown}</div></div>
						<div><a href='" . JURI::base() . "index.php/pasportlog?user={$userid}'>{$name}</a><strong>{$profilebiz_link}</strong>{$mobilnik}{$telefon}{$email}{$web}{$fio}{$info_img}</div>
						<div><img src='" . JURI::base() . "images/zvezdaadd.png' title='{$title_star}' data-title1='{$datatitle_star1}' data-title2='{$datatitle_star2}' class='{$class_star} {$id_company}'/></div>
						<div>{$cartype}<div>свободно</div>{$freeshape}</br>{$weight}</div>
						<div><a href='" . JURI::base() . "index.php/admin-truck?{$url}'>{$adress}</a><div>{$length} км</div><time datetime='{$datestart2}'>{$datestart}</time> - <time datetime='{$datestop2}'>{$datestop}</time>{$totaladres}<img src='../../images/yandplace.png' class='opmap'></div>
						<div>{$comment}</div>
						<div><img src='" . JURI::base() . "images/addroadunloged.png' class='addfavouritesroad' data-title1='Заявка отправлена' data-title2='Заявка отменена' id='{$userid} {$id_company} {$idroad}'/></div>
						</div>";						
						// обнуляем все данные из предыдущего прохода
						$totaladres = "";$length=0.0;$flagstart=0;$flagstop=0;$flagstartnow=0;$flagstopnow=0;
						$datestart="";$datestart2="";$datestartempty="";
						$datestop="";$datestop2="";$datestopempty="";
						$adress1="";$adresempty1="";
						$adress2="";$adresempty2="";
						$predatestop="";
						$firstlength=0;
						$comment="";
						$i=0;
					}
					$firstnewloop=0;					
					// если зашли первый раз по выбранному idroad выбираем из таблицы Юзер все данные
					if ( !$totaladres ) {
						$avatitle = "";
						$profilebiz = ( !empty( $row[ 'type' ] ) ) ? $row[ 'type' ] : 'Грузоперевозчик';
						$profilebiz_link = ( !empty( $row[ 'type' ] ) ) ? $row[ 'type' ] : '';
						if ( !empty( $row[ 'avatarka' ] ) ) {
							$avatar = JURI::base() . "images/avatar/useravatar/{$row['avatarka']}";
							$avatitle = " title='{$profilebiz} {$row['name']}'";
						} else
							$avatar = JURI::base() . "images/emptyavatargrey.png";

						if ( !empty( $row[ 'mobilnik' ] ) ) {
							$mobilnik = "<div class='telbox'><img src='" . JURI::base() . "images/telefon.png'/>";
							$masjsom = json_decode( $row[ 'whap_vib' ] );
							$mobilnik .= ( $masjsom[ 0 ][ 0 ] == 1 ) ? "<img src='" . JURI::base() . "images/whap.png'/>": "";
							$mobilnik .= ( $masjsom[ 0 ][ 1 ] == 1 ) ? "<img src='" . JURI::base() . "images/viber.png'/>": "";
							$mobilnik .= "<div>" . $row[ 'mobilnik' ] . "</div></div>";

						} else $mobilnik = "";

						if ( !empty( $row[ 'telefon' ] ) ) {
							$telefon = "<div class='telbox'><img src='" . JURI::base() . "images/telefon.png'/>";
							$masjsom = json_decode( $row[ 'whap_vib' ] );
							$telefon .= ( $masjsom[ 1 ][ 0 ] == 1 ) ? "<img src='" . JURI::base() . "images/whap.png'/>": "";
							$telefon .= ( $masjsom[ 1 ][ 1 ] == 1 ) ? "<img src='" . JURI::base() . "images/viber.png'/>": "";
							$telefon .= "<div>" . $row[ 'telefon' ] . "</div></div>";

						} else $telefon = "";

						$email = ( strpos( $row[ 'email' ], "No@No" ) !== false ) ? "" : "<div class='telbox'><img src='" . JURI::base() . "images/maillogot.png'/><div>" . $row[ 'email' ] . "</div></div>";

						$web = ( !empty( $row[ 'web' ] ) ) ? "<div class='telbox'><img src='" . JURI::base() . "images/ie.png'/><a href='{$row['web']}'>" . $row[ 'web' ] . "</a></div>" : "";

						$leninfo=strlen($row[ 'information' ]);
						if ( !empty($row[ 'information' ]) && $leninfo>=55) {
							$pos1 = strpos( $row[ 'information' ], " ", 45 );
							if($pos1!==false) $info_img = trim(substr( $row[ 'information' ], 0, $pos1 )) . "...";
							else $info_img = trim($row[ 'information' ]);
							$info_img = "<br/><a class='support' tabindex='1'><span class='info_click'>i</span> <div>{$info_img}</div> <span class='info_tip'>".nl2br($row['information'])."</span></a>";
						} 
						elseif( !empty($row[ 'information' ]) && $leninfo==45) {
							$info_img = trim($row[ 'information' ]);
							$info_img = "<br/><a class='support' tabindex='1'><span class='info_click'>i</span> <div>{$info_img}</div> <span class='info_tip'>".nl2br($row['information'])."</span></a>";
						} 
						else $info_img = "";

						$userid = $rand->getcodeid( $row[ 'usrid' ] );
						$id_company = ( !empty( $row[ 'id_company' ] ) ) ? $rand->getcodeid( $row[ 'id_company' ] ) : $userid;
						$idroad= $rand->getcodeid( $row['idroad'] );

						if ( !empty( $row[ 'favorid' ] ) ) {
							$class_star = "delfavorites";
							$datatitle_star1 = "Удален из избранного";
							$datatitle_star2 = "Добавлен в избранное";
							$title_star = "Удалить грузоперевозчика из избранного";
						} else {
							$class_star = "addfavourites";
							$datatitle_star1 = "Добавлен в избранное";
							$datatitle_star2 = "Удален из избранного";
							$title_star = "Добавить грузоперевозчика в избранное";
						}						
						$likeup=$row['likeup'];
						$likedown=$row['likedown'];
						$name=$row['name'];
						$fio=$row['fio'];
					}//конец первого захода					
					//ниже суммируем длинну, формируем полный маршрут и дату старт стоп 
					//$pos1 = strpos( $row[ 'adresstart' ], "(" );
					//$pos2 = strpos( $row[ 'adresstart' ], ")" );
					//if ( $pos1 !== false && $pos2 !== false )
						//$adresstart = trim( substr_replace( $row[ 'adresstart' ], "", $pos1, $pos2 - $pos1 + 1 ) );
					//else $adresstart = trim( $row[ 'adresstart' ] );
					$i++;
					$adresstart=getadress($row[ 'adresstart' ]);					
					if(mb_stripos( $row[ 'adresstart' ],  $fromcity)!==false) 
					{
						$flagstart=1;
						$flagstartnow=1;
					}
					else $flagstartnow=0;
					
					if($flagstop &&  $flagstartnow)//можно опустить поиск совпадений по старту для исключения лишних операций поиска в строке когда это уже не нужно установили в 0 просто, чтобы присвоить переменной знач-е, тк не повлияет на результать в дальнейшем при данных условиях
					$flagstopnow=0;
					elseif(mb_stripos( $row[ 'adresstart' ],  $tocity)!==false) 
					{
						$flagstop=1;
						$flagstopnow=1;
					}
					else 
						$flagstopnow=0;											
					//0+1 1+0 1+1 формируем полный маршрут totaladres, если старт совпали подсвечиваем span, если нет прибавляем
					if($flagstartnow || $flagstopnow)
					{
						$totaladres.="<div><b>".$i."</b><span>".$adresstart."</span></div> - ";
						
						$cargotype = ( !empty( $row['cargotype'] ) ) ? "<div class='itcargo'>".$row['cargotype']."</div>" : "";
						$packt = ( !empty( $row['pkt'] ) ) ? "<div class='itpkt'>".$row['pkt']."</div>" : "";
						$subcomment = ( !empty( $row['comment'] ) ) ? "<div class='itcom'>".nl2br($row[ 'comment' ])."</div>" : "";
						if($cargotype || $packt || $subcomment)
							$comment.="<div class='comitem'><b>".$i."</b>".$cargotype.$packt.$subcomment."</div>";
					}
					else
						$totaladres.="<div><b>".$i."</b>".$adresstart."</div> - ";
					//закрывающий адрес стоп будет добавлен в начале следующего цикла или в конеце после всех циклов
					// length прибавляем только в случае 1-0					
					if($flagstart && !$flagstop) $length+=(float)$row['length'];
					//записываем первый length
					if(!$firstlength) $firstlength=$row['length'];
					//Первый 1-0, дата старт записывается когда срабатывает первый 1-0, первый раз определяется пустотой переменной дата
					if($flagstart && !$flagstop && !$datestart2)
					{
						$datestart = date( "d.m.Y H:i", strtotime( $row[ 'datestart' ] ) );
						$datestart2 =  $row[ 'datestart' ];
						$adress1=$adresstart;
						$freeshape = ( !empty( $row[ 'freeshape' ] ) ) ? "<span>".round( $row[ 'freeshape' ], 2 )."</span> м. куб": "";
						$weight = getweight($row[ 'weight' ]);
						$cartype=getcartype($row[ 'cartype' ], $mascartypes);
					}
					elseif(!$datestartempty) //на всякий случай, чтобы дата старт не была пустой при форс мажоре
					{
						$datestartempty = date( "d.m.Y H:i", strtotime( $row[ 'datestart' ] ) );
						$adresempty1 = $adresstart;
					}						
					//Первый 1-1, дата стоп записывается когда срабатывает первый 1-1, первый раз определяется пустотой переменной дата
					// в конце цикла проверяем наличие дата стоп, тк в выборке из одной строки это условие не сработает
					if($flagstart && $flagstop && !$datestop2)
					{
						if(!$datestart2)//случай когда и флагстарт и фластоп = 1 1 при первом проходе на пример каз-каз, сюда же засунем и адрес 1 и адрес 2 тк они работают по одинаковой логике
						{
							$datestart = date( "d.m.Y H:i", strtotime( $row[ 'datestart' ] ) );
							$datestart2 =  $row[ 'datestart' ];
							$datestop = date( "d.m.Y H:i", strtotime( $row[ 'datestop' ] ) );
							$datestop2 = $row[ 'datestop' ];
							$adress1=$adresstart;							
							$masexplode = explode( ",", $row[ 'adresstop' ] );
							$ccc = count($masexplode);
							if($ccc>1) $adress2  = trim($masexplode[$ccc-1]);
							else $adress2  = trim($masexplode[0]);
							$freeshape = ( !empty( $row[ 'freeshape' ] ) ) ? "<span>".round( $row[ 'freeshape' ], 2 )."</span> м. куб": "";
							$weight = getweight($row[ 'weight' ]);
							$cartype=getcartype($row[ 'cartype' ], $mascartypes);
						}
						else{
							$datestop = date( "d.m.Y H:i", strtotime( $predatestop ) );
							$datestop2 = $predatestop;
							$adress2=$adresstart;// начало маршрута зеленым цветом  справа от тире
						}						
					}
					elseif(!$datestopempty) //на всякий случай, чтобы дата стоп не была пустой при форс мажоре
					{
						$datestopempty = date( "d.m.Y H:i", strtotime( $row[ 'datestop' ] ) );				
						$adresempty2=getadress($row[ 'adresstop' ]);
					}					
					// переменная нужна чтобы закрыть полный адрес в конце текуцего цикла
					$preadrstop = $row[ 'adresstop' ];
					//записываем чтобы в следующем проходе работать с ней выше
					$predatestop = $row[ 'datestop' ];
				}//конец while ( $row = $result->fetch_assoc() ) {
			}			
		} 
		while ( $mysqli->more_results() && $mysqli->next_result() );
		
		$adresstop=getadress($preadrstop);							
		if(mb_stripos( $preadrstop,  $tocity)!==false)
		{
			$totaladres.="<div><span>".$adresstop."</div></span>";
			if(!$adress2) $adress2=$adresstop;
		}
		else $totaladres.="<div>".$adresstop."</div>";
		$totaladres="<div class='total_puth'>".$totaladres."</div>";		
		if(!$adress1) $adress1=$adresempty1;
		if(!$adress2) $adress2=$adresempty2;
		$adress=$adress1." - ".$adress2;						
		$urladres1=geturl($adress1);
		$urladres2=geturl($adress2);
		$url = "start=" . urlencode( $urladres1 )."&stop=" . urlencode( $urladres2 );						
		if(!$length)$length=$firstlength;					
						
		if(!$datestop2)//если в цикле всего один проход и дата стоп не установилась 
		{
			$datestop = date( "d.m.Y H:i", strtotime( $predatestop ) );
			$datestop2 = $predatestop;
		}
		if(!$datestart) {$datestart=$datestartempty; $datestart2=$datestartempty;}
		if(!$datestop) {$datestop=$datestopempty; $datestop2=$datestopempty;}		
		$str .= "<div class='road_truck'>
		<div><img src='{$avatar}'{$avatitle}/><div class='likeup'><img src='" . JURI::base() . "images/likeup.png'/>{$likeup}</div><div class='likedown'><img src='" . JURI::base() . "images/likedown.png'/>{$likedown}</div></div>
		<div><a href='" . JURI::base() . "index.php/pasportlog?user={$userid}'>{$name}</a><strong>{$profilebiz_link}</strong>{$mobilnik}{$telefon}{$email}{$web}{$fio}{$info_img}</div>
		<div><img src='" . JURI::base() . "images/zvezdaadd.png' title='{$title_star}' data-title1='{$datatitle_star1}' data-title2='{$datatitle_star2}' class='{$class_star} {$id_company}'/></div>
		<div>{$cartype}<div>свободно</div>{$freeshape}</br>{$weight}</div>
		<div><a href='" . JURI::base() . "index.php/admin-truck?{$url}'>{$adress}</a><div>{$length} км</div><time datetime='{$datestart2}'>{$datestart}</time> - <time datetime='{$datestop2}'>{$datestop}</time>{$totaladres}<img src='../../images/yandplace.png' class='opmap'></div>
		<div>{$comment}</div>
		<div><img src='" . JURI::base() . "images/addroadunloged.png' class='addfavouritesroad' data-title1='Заявка отправлена' data-title2='Заявка отменена' id='{$userid} {$id_company} {$idroad}'/></div>
		</div>";		
		$result->free();
	}
}
//если никакой формы не было отправлено и по ссылке не переходили
elseif ( isset( $query1 ) ) {
	if ( $mysqli->multi_query( $query1 ) ) {
		$mysqli->next_result();
		$mysqli->next_result();
		if ( $result = $mysqli->store_result() ) {
			$idroad=-1;
			$idusser=-1;
			$accum="";
			$str="";
			$ii=0;
			while ( $row = $result->fetch_assoc() ) {
				$ii++;
				$coordinats.=$row['delivestartx']*1.2."-".$row['delivestarty']*1.2."-".$row['delivestopx']*1.3."-".$row['delivestopy']*1.3." ";
				if($idroad!=$row['idroad'] || $idusser!=$row['iduser'])
				{
					if($ii>1)// зашли не первый раз
					{
						$str.="<div class='allroad'>{$accum}</div>";
						$accum=BieldString( $row, $mascartypes, $rand );
						$idroad=$row['idroad'];
						$idusser=$row['iduser'];
					}
					else{//зашли первый раз
						$accum.=BieldString( $row, $mascartypes, $rand );
						$idroad=$row['idroad'];
						$idusser=$row['iduser'];
					}
				}
				else
					$accum.=BieldString( $row, $mascartypes, $rand );		
			}
			$str.="<div class='allroad'>{$accum}</div>";
			$result->free();
		}
	}
}
elseif ( isset( $query3 ) ) {
	$db->setQuery( $query3);
	$result = $db->loadAssocList();
	$str = "";
	if ( !empty( $result ) ) {
		foreach ( $result as $row ) {
			$coordinats.=$row['delivestartx']*1.2."-".$row['delivestarty']*1.2."-".$row['delivestopx']*1.3."-".$row['delivestopy']*1.3." ";
			$str.=BieldString( $row, $mascartypes, $rand );
		}
	}
}
else {}
if ( !empty( $str ) ) {
	echo "<div class='div_header'><div></div><div>Грузовладельцы, телефон, e-mail</div><div></div><div>Кузов, объем, вес</div><div>Маршрут перевозки груза, расстояние, дата</div><div>Название груза, упаковка, комментарий</div><div></div></div>
	{$str}
	<div class='divmap_total'>
	<div class='divmap'><iframe class='myiframe' scrolling='no'>Ваш браузер не поддерживает плавающие фреймы!</iframe></div>
	</div>
	<input type='hidden' class='hidden-data' value='{$coordinats}'>";
	echo $paginator;
	echo "<div class='greybackground'><div class='listitem'><div class='close_list'>закрыть</div><div class='listitemtext'></div></div></div>";
	echo "<input type='hidden' id='uuu' value='{$curent_id}'>";
	echo "<input type='hidden' id='fff' value='{$curent_id_firm}'>";
} else echo "<div class='nofinde'>Упс! Пусто.</div>";