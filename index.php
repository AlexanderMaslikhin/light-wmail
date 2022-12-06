<?php

//ini_set('error_reporting', E_ALL);
//ini_set('display_errors', 1);
//ini_set('display_startup_errors', 1);

include_once 'imap-core.php';
include_once 'sess.php';

require_once 'config.php';
require_once 'functions.php';

iSession::start();


if( iSession::authorized() ) { //authorized//
	//getting inline images or attaches
	if( !empty($_GET['type']) && iSession::isSet('uid') ) {

		$content = getBinContent($_GET['type'], $_GET['name']);
		header("Content-Type: ".$content["type"]);
		if( $_GET['type'] == "attach") {
			header('Content-Description: File Transfer');
			header("Content-Disposition: attachment; filename=\"".$_GET['name']."\"");
		}
		echo $content['body'];
		exit(1);
	}
	$postData = file_get_contents('php://input');
	$request = json_decode($postData, true);
	if(!$request && !empty($postData)) {
		header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
		echo "Error in JSON data format: ".json_last_error_msg(). "postData = ".$postData;
		iSession::destroy();
		die();
	}
	if(empty($postData)) {
		printWorkPage();
		exit();
	}
	header("Content-type: application/json");
	switch($request['action']) {
		case "get_dir_tree":
			echo getTree();
			break;
		case "getBoxList":
			if(iSession::getvar('uid')) iSession::unsetvar("uid");
			iSession::setvar('boxpath', $request['boxpath']);
			echo getBoxList($request['boxpath'], $request['rowCount']);
			break;
		case "getBoxListNext":
			echo getBoxListNext($request['boxpath'], $request['start'], $request['rowCount']);
			break;
		case "logout":
			iSession::destroy();
			echo json_encode(["logout" => true], JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
			break;
		case "viewMessage":
			iSession::setvar("uid", $request['uid']);
			$ret = getMessageContent($request["boxpath"],$request["uid"]);
			echo json_encode($ret, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE);
			break;
	}
	exit();
}

if( isset($_POST['bxod']) ) { //trying to login
	$login = strip_tags($_POST['login']);
	$login = htmlspecialchars($login);
	$pass = strip_tags($_POST['password']);
	$pass = htmlspecialchars($pass);
	if( checkLogin($login,$pass) ) {
		iSession::setvar("email",$login);
		iSession::setvar("pass",$pass);
		iSession::setvar("authorized", true);
		iSession::changeId();
		printWorkPage();
	}
	else {
		printLoginForm("Wrong email or password");
	}
	exit();
}

printLoginForm();

?>