<?php
include_once 'mimeparse.php';

error_reporting(E_ALL & ~E_NOTICE);

class imapException extends Exception {
	public function __construct($message) {
		parent::__construct($message);
		set_exception_handler(function ( $e ) {
			echo $e->getCode(),": ",$e->getMessage(),"<br>\n";
			echo $e->getFile(),": ",$e->getLine(),"<br>\n";
		} );
	}
}

class imapMime {
	public $type;
	public $encoding = "";
	private  $parts_count = 0;
	private $my_no; //0 if  root 
	public $parts = array(); //array of parts imapMime type

	public function __construct($num = 0) {
		$this->my_no = $num;
	}

	public function __get($prop) {
		switch ($prop) {
			case 'my_no':
				return $this->my_mo;
			case 'parts_count':
				return $this->parts_count;
		}
	}

	public function addPart($part) {
		$this->parts[] = $part;
		$this->parts_count++;
	}
}

class imapFolder {
	public $name;
	public $fullpath;
	public $options = array();
	public $children = array(); // array of imapDirs
	public $messages;
	public $unseen;
}

class imap {
	private $conn;
	private $error;
	private $cmdid = 1;
	private $response = array();
	private $cmd_status;
	private $cmd_status_str;
	private $encoding = 'UTF-8';
	private $mime_message;
	private $logined = false;
	
	public function __destruct() {
//		echo "\ndisconnecting.....\n";
		$this->close();
	}

	public function init($addr, $port = 143, $starttls = false, $timeout = 15) {
	
		$context = stream_context_create(); 
		stream_context_set_option($context, 'ssl', 'verify_peer', false);
		stream_context_set_option($context, 'ssl', 'verify_peer_name', false);
		stream_context_set_option($context, 'ssl', 'allow_self_signed', true);

//		if(!$this->conn = fsockopen($addr, $port, $errno, $errstr, $timeout)) {
		if(!$this->conn = stream_socket_client($addr.":".$port, $errno, $errstr, $timeout, STREAM_CLIENT_CONNECT, $context) ) {
			$this->error = "Connection error $errno: $errstr";
			return false;
		}
		if($port == 993) {
			if(!stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_ANY_CLIENT)) return false;
		}

		if($port == 143 && $starttls) {
			if(!$this->starttls()) return false;
		}

		return true;
	}

	private function close() {
		if(isset($this->conn)) {	
			if( $this->logined ) $this->cmd("LOGOUT");
			fclose($this->conn);
		}	
	}

	private function cmd($command,$throwable = false) {
//emty response buffer
		while(!empty($this->response)) {
			array_pop($this->response);
		}
//sending LOGIN command
		$command = "$this->cmdid $command\r\n";
		fwrite($this->conn, $command);
//getting response
		$id = 0;
		while($this->cmdid != intval($id)) {
			$line = fgets($this->conn);
			$this->response[] = $line;


			//$id_arr = explode(" ", $line, 2);
			//if(!empty($id_arr[0])) {
			if( preg_match("/^(\d+)\s/",$line,$matches) && $this->cmdid == intval($matches[1])) {
					list($id,$this->cmd_status, $this->cmd_status_str) = explode(" ", $line, 3);
			}
		}
		array_pop($this->response); // removing status response
		$this->cmdid++;
		if($this->cmd_status == "OK") {
			return true;
		}
		if($throwable) {
			echo "DEBUG-COMMAND: ".$command."\n";
			throw new imapException($this->cmd_status.": ".$this->cmd_status_str);
		}
		return false;
	}

	public function login($user, $password) {
		if($this->cmd("LOGIN $user $password")) {
			$this->logined = true;
			return true;
		}
		$this->error = "Login error: $this->cmd_status_str";
		return false;
	}

	public function starttls() {
/*		$opts = array(
			'verify_peer' => false,
			'allow_self_signed' => true
		);
*/
		$this->cmd("STARTTLS", true);
		return stream_socket_enable_crypto($this->conn, true, STREAM_CRYPTO_METHOD_ANY_CLIENT);
	}

	public function selectFolder($folder) {
		$this->cmd("SELECT \"".$folder."\"",true);
	}

	private function getSubfolders($folder, &$objArr) {  //$folder - root folder with delimiter, $objArr - array of chldren objects
		$this->cmd('LIST '.'"'.$folder.'"'.' "%"', true);
		$response = $this->response;
		foreach($response as $line) {
			$line = trim($line);
			preg_match('/^\*\s+LIST\s+\((.*)\)\s+\"(.*)\"\s+(.*)$/',$line,$matches);

			if( !isset($delim) ) $delim = $matches[2];
			$subfold = new imapFolder();
			$subfold->options = explode(" ",preg_replace("~\\\\~", "",$matches[1]));
			$name = trim($matches[3],"\"\t\n\r\0\x0B");
			if($delimpos = intval(strrpos($name,"."))) $delimpos++;
			$subfold->name = mb_convert_encoding(substr($name,$delimpos),$this->encoding,"UTF7-IMAP");
			$subfold->fullpath = $name;
			if( in_array("HasChildren", $subfold->options) ) {
				$this->getSubfolders($name.$delim, $subfold->children);
			}
			$this->cmd("STATUS \"$name\" (messages unseen)",true);
			$stat_resp = $this->response;

			preg_match('/\(MESSAGES\s+(\d+)\s+UNSEEN\s+(\d+)\)/', trim($stat_resp[0]), $matches);
			$subfold->messages = $matches[1];
			$subfold->unseen = $matches[2];
			$objArr[] = $subfold;
		}
	}

	private function getStructure($uid) {
		$this->cmd("UID FETCH ".$uid." BODYSTRUCTURE",true);
		$structure = "";
		if( count($this->response) > 1 ) {
			for($i=0;$i<count($this->response);$i++) {
				$this->response[$i] = trim($this->response[$i]);
				if(preg_match("/\{(\d+)\}$/",$this->response[$i],$matches) === 1) {
					$param_len = intval($matches[1]);
					$this->response[$i+1] = "\"".substr($this->response[$i+1],0,$param_len)."\"".substr($this->response[$i+1],$param_len);
				}
				$structure.= preg_replace("/\{\d+\}$/", "", $this->response[$i]);
			}
		}
		else {
			$structure = trim($this->response[0]);
		}
//		$this->mime_message = new bodyMime;
//		$this->mime_message->structure = $structure;
//		return $this->mime_message;
		return $structure;
	}


	private function getEnc($str) {
		if(preg_match("/=\?(.*)?\?.?\?/U", $str, $matches)) {
			return strtoupper($matches[1]);
		}
		return "";
	}

	private function my_encode($str) {
		if(preg_match_all("/=\?(.*)?\?B?\?(.*)?\?=/U", $str, $matches)) {
			return preg_replace("/=\?.*\?B?\?.*\?=/",base64_decode(implode("",$matches[2])),$str);
		}
		if(preg_match_all("/=\?(.*)?\?Q?\?(.*)?\?=/U", $str, $matches)) {
			return preg_replace("/=\?.*\?Q?\?.*\?=/",quoted_printable_decode(implode("",$matches[2])),$str);
		}
	}

	private function getField($uid, $field) {
		$this->cmd("UID FETCH ".$uid." body.peek[header.fields (".$field.")]", true);
// join array into string
		$this->response = array_map(function($a) {
			return trim($a);
		}, $this->response);
		$retval = implode("", array_slice($this->response, 1 ,array_search("",$this->response)));
		if( $enc = $this->getEnc($retval) ) {
			$retval = $this->my_encode($retval);
			if($enc !== $this->encoding) {
				$retval = iconv($enc,$this->encoding,$retval);
			}
		}
		return $retval;
	}

	private function getFolderMessCount($folder) {
		$this->cmd("STATUS ".$folder." (MESSAGES)");
		preg_match("/\(.*\s(\d+)\)/",$this->response[0],$matches);
		$mess_count = intval($matches[1]);	
		return $mess_count;
	}

	private function getFlags( $uid ) {
		$this->cmd("UID FETCH ".$uid." FLAGS", true);
		preg_match("/FLAGS\s\((.*)\)\)/",$this->response[0],$matches);
		return explode(" ",preg_replace("~\\\\~", "", $matches[1]));
	}

	public function getFolders() {
		$dtree = array(); //dir tree
		$this->getSubfolders("", $dtree);
		return json_encode($dtree, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE );
	}

	public function getMessagesListBrief($folder, $start, $count, $orderby = 'Received-Date', $desc = true) {
		$mess_count = $this->getFolderMessCount($folder);
		if( $start > $mess_count || $mess_count === 0 ) {
			 return json_encode(array( 'list'=>array(), 'first' => 0)); //emty array
		}
		if( $mess_count < ($start + $count - 1) ) {
			$count = $mess_count - $start + 1;
		}
		$stop = $start + $count - 1;
		$this->selectFolder($folder);
		$uid = array();
		$messList = array();
		$this->cmd("UID SEARCH ".$start.":".$stop, true);
		$uid = explode(" ",trim(substr($this->response[0],9)));
//		$date = new DateTime();		
		foreach($uid as $val) {
			$this->cmd("UID FETCH ".$val." body.peek[header.fields (date from subject)]", true);
			if(!$curlist = iconv_mime_decode_headers(implode("",array_slice($this->response,1,-2)),0)) {
				$this->cmd("UID FETCH ".$val." body.peek[header.fields (date)]", true);
				$curlist = iconv_mime_decode_headers(implode("",array_slice($this->response,1,-2)),0);
				$curlist['From'] = substr($this->getField($val,"from"),6);
				$curlist['Subject'] = substr($this->getField($val,"subject"),9);
			}
			$this->cmd("UID FETCH ".$val." internaldate", true);
			$curlist['Received-Date'] = new DateTime();
			$curlist['Received-Date']->setTimestamp(strtotime(strstr(substr(strstr($this->response[0],'"'),1),'"',true)));
			$curlist['uid'] = $val;
			$mailstruct = new bodyMime();
			$mailstruct->structure = $this->getStructure($val);
			if(isset($mailstruct->structure['par_list']['charset']) && $this->encoding != strtoupper($mailstruct->structure['par_list']['charset']) ) {
				$curlist["Subject"] = iconv(strtoupper($mailstruct->structure['par_list']['charset']), $this->encoding, $curlist["Subject"]);
			}
			$curlist['attachments'] = $mailstruct->attachments;
			$curlist['new'] = !in_array("Seen",$this->getFlags($val));
			if($this->encoding !== $mailstruct->charset) {
				foreach($curlist['attachments'] as &$att_val) {
					$att_val = iconv($mailstruct->charset, $this->encoding,$att_val);
				}
				unset($att_val);
			}
//			$date = new DateTime();		
//			$date->setTimestamp(strtotime($curlist["Date"]));
//			$curlist["Date"] = $date;
			$messList[] = $curlist;
		}
		if($orderby == 'Received-Date') {
			if($desc) {
				usort($messList, function($a,$b) {
					return $b["Received-Date"]->getTimestamp() - $a["Received-Date"]->getTimestamp();
		 		});
		 	}
		 	else {
				usort($messList, function($a,$b) {
					return $a["Received-Date"]->getTimestamp() - $b["Received-Date"]->getTimestamp();
		 		});		 		
		 	}
	 	}
	 	else {
	 		if($desc) {
	 			function build_sorter($key) {
	 				return function ($a,$b) use ($key) {
	 					return strcmp($b[$key],$a[$key]);
	 				};
	 			}
	 		}
	 		else {
	 			function build_sorter($key) {
	 				return function ($a,$b) use ($key) {
	 					return strcmp($a[$key],$b[$key]);
	 				};
	 			}
	 		}
	 		usort($messList, build_sorter($orderby));
	 	}

	 	$messList = array_map(function($a){
			$a["Received-Date"] = $a["Received-Date"]->format("c");
//			$a["Date"] = $a["Date"]->format("c");
			return $a;
		},$messList);
		$retval = [
			"list" => $messList,
			"first" => $start,
			"fullCount" => $mess_count
		];
		return json_encode($retval, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE );
	}

	public function getLast($folder, $count=10) {
		$mess_count = $this->getFolderMessCount($folder);
		$count = ($mess_count > $count)?$count:$mess_count;
		$tmp = $mess_count-$count+1;
		return $this->getMessagesListBrief($folder, $mess_count-$count+1,$count);
	}

	public function getFirst($folder, $count=10) {
		return $this->getMessagesListBrief($folder, 1, $count, "Received-Date", false);
	}

	public function getHeaders($uid) {
		$this->cmd("UID FETCH ".$uid." body[header]");
		if(!isset($this->mime_message)) {
			$this->mime_message = new bodyMime();
		}
		$bytes = intval(substr($this->response[0], strpos($this->response[0],'{')+1, strpos($this->response[0],'}') - strpos($this->response[0],'{')-1));
		$header_str = substr(implode("",array_slice($this->response,1)), 0, $bytes);
//		$this->mime_message->headers = $header_str;
		return $header_str;

	}

	public function getContent($uid) {
		$this->mime_message = new bodyMime();
		$this->mime_message->structure = $this->getStructure($uid);

		$this->cmd("UID FETCH ".$uid." body.peek[header.fields (date from subject)]", true);
		if(!$headers = iconv_mime_decode_headers(implode("",array_slice($this->response,1,-2)),0)) {
			$this->cmd("UID FETCH ".$uid." body.peek[header.fields (date)]", true);
			$headers = iconv_mime_decode_headers(implode("",array_slice($this->response,1,-2)),0);
			$headers['From'] = substr($this->getField($uid,"from"),6);
			$headers['Subject'] = substr($this->getField($uid,"subject"),9);
		}
		$this->mime_message->headers = $this->getHeaders($uid);
//		print_r($this->mime_message->structure);
		foreach($this->mime_message->parts as $p) {
			$this->cmd("UID FETCH ".$uid." body[".$p."]");
			$bytes = intval(substr($this->response[0], strpos($this->response[0],'{')+1, strpos($this->response[0],'}') - strpos($this->response[0],'{')-1));
			$part_content = substr(implode("",array_slice($this->response,1)), 0, $bytes);
			$this->mime_message->setBody($p,$part_content);
		}
		return array( "headers" => $headers, "structure" => $this->mime_message->structure, "attachments" => $this->mime_message->attachments);
	}

	public function __get($property) {
		switch($property)
		{
			case 'error':
				return  $this->error;
				break;
			case 'response':
				return $this->response;
				break;
			case 'cmd_status':
				return $this->cmd_status;
			case 'cmd_status_str':
				return $this->cmd_status_str;
			case 'encoding':
				return $this->encoding;
			default: return NULL;
		}
	}

	public function __set($name, $value) {
		switch($name) {
			case 'encoding':
				$this->encoding = $value;
		}
	}
}

?>