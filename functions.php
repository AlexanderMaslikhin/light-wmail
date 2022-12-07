<?php
include_once 'imap-core.php';
include_once 'sess.php';
require_once 'config.php';


$imap = new imap();

function printLoginForm($message = "") {
 	global $templates_path;
	require $templates_path."bxod.html";
}

function checkLogin( $login, $pass ) {
	global $template_path, $imap_server, $imap_port, $security, $imap;

	if(!$imap->init($imap_server, $imap_port[$security], $security == 'STARTLS') ) {
		print $imap->error;
		return false;
	}

	if( !$imap->login($login, $pass) ) {
		print $imap->error;
		return false;
	}
	else {
		return true;
	}
}

function printWorkPage() {
	global $template_path;
	require $template_path."work.tpl";
}

function getTree() {
	global $imap;
	if( !checkLogin(iSession::getvar('email'), iSession::getvar('pass')) ) {
		die(1);
	}
	$tree = $imap->getFolders();
	return $tree;
}

function getBoxList($boxpath, $rows = 20) {
	global $imap;
	if( !checkLogin(iSession::getvar('email'), iSession::getvar('pass')) ) {
		die(1);
	}
	$mlist = $imap->getLast($boxpath, $rows);
//	print_r($mlist);
	return $mlist;
}

function getBoxListNext($boxpath, $start, $rows = 20) {
	global $imap;
	if( !checkLogin(iSession::getvar('email'), iSession::getvar('pass')) ) {
		die(1);
	}
	$mlist = $imap->getMessagesListBrief($boxpath, $start, $rows);
//	print_r($mlist);
	return $mlist;
}


function decode($encoding = 'base64', $data) {
	switch($encoding) {
		case 'base64': 
			return base64_decode($data);
		case 'quoted-printable':
			return quoted_printable_decode($data);
		default:
			return $data;
	}
}

function getMessageContent($boxpath, $uid) {
	global $imap;
	if( !checkLogin(iSession::getvar('email'), iSession::getvar('pass')) ) {
		die(1);
	}
	$imap->selectFolder($boxpath);
	$content = $imap->getContent($uid);
	$ret['headers'] = $content['headers'];
	$structure = $content['structure'];
	if($structure['type'] !== 'multipart') {
		if($structure['subtype'] == "html") 
			$ret['body'] = parse_html_body(decode($structure['encoding'],$structure['body']), strtoupper($structure["par_list"]["charset"]));
		if($structure['subtype'] == 'plain') $ret['body'] = nl2br(htmlentities(decode($structure['encoding'], $structure['body']), ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE));
	}
	else {
			$tmp = parse_multipart($structure['parts'], $structure['subtype']);
			$ret['body'] = $tmp['body'];
			$ret['attaches'] = $content['attachments'];
		}
	return $ret;
}


$fh = fopen("/tmp/phptest.log", "w+");

function parse_multipart($struct, $subtype) {
	global $fh;
// get CIDs
    fwrite($fh, "parent type => $subtype\n");
	$ret = array( 'body' => "", 'attaches' => array() );
	foreach($struct as $part) {
		if($part['type'] === 'multipart') {
			$tmp = parse_multipart($part['parts'], $part['subtype']);
			$ret['body'] = $ret['body'].$tmp['body'];
			$ret['attaches'] = array_merge($ret['attaches'],$tmp['attaches']);
		}
		if($part['subtype'] === 'html') {
			$ret['body'] = $ret['body'].parse_html_body(decode($part['encoding'],$part['body']), strtoupper($part["par_list"]["charset"]));
//			$ret_body = $ret_body.iconv(strtoupper($part['par_list']['charset']), "UTF-8", decode($part['encoding'],$part['body']));
		}
		if($part['type']=='text' && $part['subtype'] == 'plain' && $subtype != 'related' && $subtype != 'alternative') {
			fwrite($fh, "========initializing ret_body with".$part['body']."\n");
			$ret['body'] = nl2br(htmlentities($ret['body'].decode($part['encoding'], $part['body']), ENT_QUOTES | ENT_HTML5 | ENT_SUBSTITUTE));
		}
		fwrite($fh, "type => ".$part['type']."\n ret_body => ".$ret['body']."\n");
//attachment
	}


/*	$ret_body = "";
	foreach($struct as $part) {
		if($part['type'] === 'multipart') {
			$ret_body = $ret_body.parse_multipart($part['parts'], $part['subtype']);
		}
		if(($subtype === 'alternative' || $subtype === 'related') && $part['subtype'] === 'html') {
			$ret_body = $ret_body.parse_html_body(decode($part['encoding'],$part['body']));
//			$ret_body = $ret_body.iconv(strtoupper($part['par_list']['charset']), "UTF-8", decode($part['encoding'],$part['body']));
		}
	}*/
	return $ret;
}

function parse_html_body($body, $charset) {
	$fh = fopen("/tmp/phpdebug.log", "w+");
	fwrite($fh, $body);
	$body = preg_replace("/cid:/", "?type=cid&name=", $body);
	$doc = new DomDocument();
	$doc->loadHTML('<meta http-equiv="Content-Type" content="text/html; charset='.$charset.'">'.$body);
	fwrite($fh, "\n================\n".$doc->saveHTML());
	$nodes = $doc->getElementsByTagName('head');
	$head = $nodes->item(0);
//remove <meta> tags
	// $metas = $doc->getElementsByTagName('meta');
	// while($metas->count()>0) {
	// 	$doc->removeChild($metas->item(0));
	// }
//modifying <style> 
	$styles = $doc->getElementsByTagName('style');
	while($styles->count()>0){
		$parent = $styles->item(0)->parentNode;
		$style = $style.stripStyle($doc->saveHTML($styles->item(0)));
		$parent->removeChild($styles->item(0));
	}
	$ret_value = $ret_value."<style>".$style."</style>";
//	if($charset != 'UTF-8') $doc->encoding = 'UTF-8';
	$headnodes = $head->childNodes;
	foreach($headnodes as $node) {
		$ret_value = $ret_value.$doc->saveHTML($node);
	}
	foreach($doc->getElementsByTagName('body')->item(0)->childNodes as $body_node) {
		$ret_value = $ret_value.$doc->saveHTML($body_node);
	}
	fwrite($fh, "\n================\n".$doc->saveHTML());
	return "<div class='class".iSession::getvar('uid')."'>".$ret_value."</div>";
}

function stripStyle($styles) {

	preg_match("#<style[^>]*?>(.+?)</style\s*?>#is", $styles, $matches);
	$styles = $matches[1];
	$styles = str_replace("<!--", "", $styles);
	$styles = str_replace("-->", "", $styles);
	$styles = preg_replace("%\/\*[^\*^\/]+\*\/%", "", $styles);
	preg_match_all("/(([^\{]+)(\{[^\}]+\}))/U",$styles,$matches);
	$i=0;
	foreach($matches[2] as $key) {
	    $key = trim($key);
	    $tags = explode(",", $key);
	    foreach($tags as &$tag) {
	    	$tag = ".class".iSession::getvar('uid')." ".$tag;
		}
    	unset($tag);
    	$key = implode(",", $tags);
    	$style_arr[$key] = $matches[3][$i];
    	$i++;
	}
	$ret = "";
	foreach($style_arr as $mod => $style) {
		$ret = $ret.$mod." ".$style."\n";
	}
	return $ret;
}

function getBinContent($type,$name) {
	global $imap;
	if( !checkLogin(iSession::getvar('email'), iSession::getvar('pass')) ) {
		die(1);
	}
	$imap->selectFolder(iSession::getvar('boxpath'));
	$content = $imap->getContent(iSession::getvar('uid'));
	$content = $content['structure'];
	switch($type) {
		case 'cid': 
			return findCid($content, $name);
		case 'attach':
			return findAttach($content, $name);
	}
}

function findAttach($str, $name) {
	$retval = NULL;
	foreach($str['parts'] as $part) {
		if( $part['type'] == 'application' && strstr($part['par_list']['name'], $name)) {
			$retval["type"] = $part["type"]."/".$part['subtype'];
			$retval["body"] = decode($part["encoding"], $part["body"]);
			return $retval;
		}
		if($part['type'] == 'multipart') {
			$retval = findAttach($part, $cid);
		}
	}
	return $retval;
}
 
function findCid($str, $cid) {
	$retval = NULL;	
	foreach($str['parts'] as $part) {
		if( isset($part['id']) && strstr($part['id'], $cid) ) {
			$retval["type"] = $part["type"]."/".$part['subtype'];
			$retval["body"] = decode($part["encoding"], $part["body"]);
			return $retval;
		}
		if($part['type'] == 'multipart') {
			$retval = findCid($part, $cid);
		}
 	}
	return $retval;
}

?>
