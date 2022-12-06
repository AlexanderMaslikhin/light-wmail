<?php

/*
RFC 3501

###single-part

* 1 FETCH (BODYSTRUCTURE ("text" "plain" ("charset" "us-ascii") NIL NIL "7bit" 21 1 NIL NIL NIL NIL))

(
	BODYSTRUCTURE 
	(
		"text" "plain" ("charset" "us-ascii") NIL NIL "7bit" 21 1 NIL NIL NIL NIL
	)
)

(BODYSTRUCTURE ((("text" "plain" ("charset" "windows-1251") NIL NIL "8bit" 2499 58 NIL NIL NIL NIL)("text" "html" ("charset" "windows-1251") NIL NIL "8bit" 7191 166 NIL NIL NIL NIL) "alternative" ("boundary" "------------EAF73BB1FEAF90108D5E1EAA") NIL NIL NIL)("application" "pgp" ("name" "22022019130048.asc") NIL NIL "7bit" 884 NIL ("attachment" ("filename" "22022019130048.asc")) NIL NIL) "mixed" ("boundary" "------------96036D19FC52097AA42EF9CD") NIL ("en-US") NIL))
BODYSTRUCTURE 
(
	1. Body-type (Content-type):"text" 
	2. Body subtype: "plain" 
	3. body parameter parenthesized list: ("charset" "us-ascii") 
	4. body id: NIL
	5. body description: NIL 
	6. body encoding: "7bit" 
	7. body size: 21 
	8. body lines(only for TEXT type): 1 
----
	a. body MD5: NIL
	b. body disposition: NIL
	c. body language: NIL
	d. body location: NIL
)


*/

class mimeException extends Exception {
	public function __construct($message) {
		parent::__construct($message);
		set_exception_handler(function ( $e ) {
			echo $e->getCode(),": ",$e->getMessage(),"<br>\n";
			echo $e->getFile(),": ",$e->getLine(),"<br>\n";
		} );
	}
}


function strip($str) {
	$arr = array();
	$level = 0; $i=1;
	$chr = substr($str,$i,1);
	while (($level !== 0) || ($chr !== ')')) {
		if($chr == '(') $level++;
		if($chr == ')') $level--;
		$i++;
		$chr = substr($str,$i,1);
	}
	$arr[] = substr($str,1,$i-1);
	$arr[] = substr($str,$i+1);
	return $arr;
}

function getParam($str) {
	$ret = array();
	if($str[0] == '"') {
		if($param = strstr($str,'" ',true)) {
			$ret[] = trim($param,'\"');
			$ret[] = substr(strstr($str,'" '),2);
		}
		else {
			  $ret[] = trim(substr($str,1),'"');
			  $ret[] = "";
		}
	}
	else {
		if(($param = strstr($str,' ',true))!==false) {
			$ret[] = $param!='NIL'?intval($param):null;
			$ret[] = trim(strstr($str,' '));		
		}
		else {
			$ret[] = $str!='NIL'?intval($str):null;
			$ret[] = "";
		}
	}
	return $ret;
}


function getParams($str) {
	$res = array();
	$arr = array();
	while($str != "") {
		list($par,$str) = getParam($str);
		$arr[] = $par;
	}
//	$arr = explode(" ", $str);
	for($i=0;$i<count($arr);$i+=2) {
		$res[$arr[$i]] = $arr[$i+1]; 
	}
	return $res;
}

class bodyMime {

	private $struct = array (
		'type' => "",
		'subtype' => "",
		'par_list' => array(),
		'id' => "",
		'description' => "",
		'encoding' => "",
		'size' => 0,
		'lines' => 0,
		'md5' => 0,
		'disposition' => "",
		'disp_params' => array(), 
		'language' => "",
		'location' => "",
		'index' => "",
		'body' => NULL,
		'parts' => array() // parts of multipart message
	);
//	private $charset = "UTF-8";
	private $body_parts_indexes = array();
	private $structure_initialized = false;
	private $encoding = "UTF-8";
/*	private $type, $subtype;
	private $par_list = array();
	private $id, $description, $encoding, $size, $lines;
	private $md5;
	private $disposition = $array();
	private $language, $location

	private $parts = array();*/
	private $headers = array();

/*	public function __construct($str = "") {
		if($str == "") return;
		$this->init($str);
	}*/

	private function init_str($bodystruct, $body = "", $headers = "" ) {
		$bodystruct = stristr($bodystruct,"BODYSTRUCTURE (");
		$bodystruct = substr($bodystruct,15,-2);
		if(!$this->parse($this->struct,$bodystruct)) {
			throw new mimeException("MIME Parsing Error: Cannot initialize body structure\n<br\>");
			exit(1);
		}
		$this->structure_initialized = true;
		$this->body_parts_indexes = $this->getBodiesIndexes($this->struct);
		return true;
	}

	private function getDisposition($str, &$part) {
		//("attachment" ("filename" "22022019130048.asc"))
		if($str[0] == '(') {
			list($str,$rest) = strip($str);
			list($part['disposition'],$str) = getParam($str);
			if($str[0] == '(') $part['disp_params'] = getParams(trim($str,"()"));
		}
		else {
			list($part['disposition'],$rest) = getParam($str);
		}
		return $rest;
	}

/*	public function toHtmlEntities($tmp_arr) {
		array_walk($tmp_arr, function(&$val,$key) {
			if( $key == 'par_list' || $key == 'disp_params') {
				$val = array_combine(array_map(function($a) {
					return htmlentities("$a",ENT_COMPAT | ENT_HTML5,$this->charset);
				}, array_keys($val)), 
				array_map(function($a) {
					return htmlentities("$a",ENT_COMPAT | ENT_HTML5,$this->charset);
				},array_values($val)));
				return;
			}
			if( $key == 'parts' ) {
				if( !empty($val) ) {
					foreach($val as &$part) {
						$part = $this->toHtmlEntities($part);
					}
					unset($part);
				}
				return;
			}
			$val = htmlentities($val);
		});
		return $tmp_arr;
	}*/

	private function parse(&$part,$str) {
		$i=0;
		while (substr($str,0,1) == '(') {
			list($newpart,$str) = strip($str);
			$part["parts"][] = array ();
			$this->parse($part["parts"][$i],$newpart);
			$i++;
		}
		if( $i>0 ) { //multipart body part
			$part['type'] = 'multipart';
//-------	"mixed" ("boundary" "------------96036D19FC52097AA42EF9CD") NIL          ("en-US")    NIL
//-------	subtype   par_list                                          disposition   language    location
			$part['subtype'] = preg_replace("/[\s+\"]/","",strstr($str,"(",true));
			list($par_list,$str) = strip(strstr($str,"("));
			$part['par_list'] = getParams($par_list);
//------- 	disposition
			$str = $this->getDisposition(trim($str),$part);
//------- language & location
			list($part['language'],$str) = getParam($str);
			list($part['location'],$str) = getParam($str);
		}
		else { //non-multipart body part
//-------- "text" "plain" ("charset" "us-ascii") NIL NIL "7bit" 21      1    NIL     NIL   NIL   NIL
//--------- type  subtype   params               id  desc  enc  size strnum  md5   dispos  lang  location
			list($part['type'],$str) = getParam($str);
			list($part['subtype'],$str) = getParam($str);
//-----------parameters list
			if( $str[0] == '(' ) {
				list($params,$str) = strip($str);
				$part['par_list'] = getParams($params);
				if( !empty( $part['par_list']['name'] )) {
					$part['par_list']['name'] = $this->check_enc_attach_name($part['par_list']['name']);
				}
//				if( array_key_exists("charset", $part['par_list']) ) {
//					$this->charset = strtoupper($part['par_list']["charset"]);
//				}
				$str = trim($str);
			}
			else {
				list($params,$str) = getParam($str);
			}
			list($part['id'],$str) = getParam($str);
			list($part['description'],$str) = getParam($str);			
			list($part['encoding'],$str) = getParam($str);
			list($part['size'],$str) = getParam($str);
			switch($part['type']) {
				case 'text':
					list($part['lines'], $str) = getParam($str);
				//lines number
					break;
				case 'rfc822':
				case 'message':
				//skip envelope + parse parts
					if($str[0] ==  '(') { // has encapsulated message
						list($env, $str) = strip($str); //skip envelope
						$str=trim($str);
						while (substr($str,0,1) == '(') {
							list($new_mess_part,$str) = strip($str);
							$part["parts"][] = array ();
							$this->parse($part["parts"][$i],$new_mess_part);
							$i++;
							$str = trim($str);
						}
						list($part['lines'], $str) = getParam($str);
					}
					break;
			}
			list($part['md5'],$str) = getParam($str);
			$str = $this->getDisposition($str,$part);
			list($part['language'],$str) = getParam($str);
			list($part['location'],$str) = getParam($str);
//			print_r($part);
		}
		return true;
	}

	private function check_enc_attach_name($str) {
		if( preg_match_all("/=\?.*\?B\?(.*)\?=/U", $str, $matches) ) {
			$arr = array_map( function($e) {
				return base64_decode($e);
			}, $matches[1]);
			return implode("",$arr);
		}
		if( preg_match_all("/=\?.*\?Q\?(.*)\?=/U", $str, $matches) ) {
			$arr = array_map( function($e) {
				return quoted_printable_decode($e);
			}, $matches[1]);
			return implode("",$arr);
		}
		return $str;
	}

	private function getBodiesIndexes(&$struct, $level = 0) {
		if($struct["type"] !== 'multipart' && ($struct['type'] !== 'message' && $struct['subtype'] !== 'rfc822')) {
			$retarr[] = ($level == 0)?1:$level."."."1";;
//			$struct["index"] = $retarr[] = ($level == 0)?1:$level."."."1";
			return $retarr;
		}
		$tmparr = array_keys($struct['parts']);
		for($i=0; $i<count($struct['parts']); $i++) {
			$retarr[] = ($level == 0)?$tmparr[$i]+1:$level.".".($tmparr[$i]+1);
//			$struct["index"] = $retarr[] = ($level == 0)?$tmparr[$i]+1:$level.".".($tmparr[$i]+1);
			if($struct['parts'][$i]['type'] == 'multipart' || ($struct['parts'][$i]['type'] == 'message' && $struct['parts'][$i]['subtype'] == 'rfc822')) {
				array_splice($retarr, count($retarr)-1, 1, $this->getBodiesIndexes($struct['parts'][$i],$retarr[count($retarr)-1]));
			}
		}
		return $retarr;
	}

	private function attachmentList($struct, &$attaches) {
//		echo "type => ".$struct['type']."\n";
		if( $struct['disposition'] == "attachment" || $struct['type'] == 'application') {
			if( !empty($struct['par_list']['name']) ) {
				$attaches[] = $struct['par_list']['name'];//$this->check_enc_attach_name($struct['par_list']['name']);
			}
		}
		if( !empty($struct['parts']) ) {
			foreach( $struct['parts'] as $val ) {
				$this->attachmentList($val,$attaches);
			}
		}
	}

	private function decode($encoding = 'base64', $data) {
		switch($encoding) {
			case 'base64': 
				return base64_decode($data);
			case 'quoted-printable':
				return quoted_printable_decode($data);
			default:
				return $data;
		}
	}

	private function decodeStr($str) {
		if( preg_match_all("/=\?.*\?B\?(.*)\?=/U", $str) ) {
			return  preg_replace_callback("/=\?(.*)\?B\?(.*)\?=/", function ($matches) {
				if( ($enc=strtoupper($matches[1])) !== $this->encoding ) return iconv( $enc, $this->encoding, base64_decode($matches[2]));
				return base64_decode($matches[2]);
			}
			, $str);
		}
		if( preg_match_all("/=\?.*\?Q\?(.*)\?=/U", $str) ) {
			return preg_replace_callback("/=\?(.*)\?Q\?(.*)\?=/", function ($matches) {
				if( ($enc=strtoupper($matches[1])) !== $this->encoding ) return iconv( $enc, $this->encoding, quoted_printable_decode($matches[2]));
				return quoted_printable_decode($matches[2]);
			}
			, $str);;
		}
		return $str;		
	}

	private function parseHeaders($hstr) {
		$hstr = preg_replace('/\\r\\n\s+/', "\n\t", $hstr);
		foreach(explode("\r\n", $hstr) as $val) {
			$key = strstr($val, ": ", true);
			$this->headers[$key] = $this->decodeStr(substr($val, strpos($val, ": ")+2));
		}
	}

	public function getHTMLView($charset = "UTF-8") {
		if($this->struct['type'] !== 'multipart') {
			$retbody = $this->decode($this->struct['encoding'],$this->struct['body']);
			if($charset != strtoupper($this->struct["par_list"]["charset"])) {
				$retbody = iconv(strtoupper($this->struct["par_list"]["charset"]), $charset, $retbody);
			}
			return $retbody;
		}
		foreach ($this->struct['parts'] as $part) {
			if($part['type'] == 'multipart' && $part['subtype'] == "alternative") {
				foreach($part['parts'] as $subpart) {
					if($subpart['type'] == 'text' && $subpart['subtype'] == 'html') {
						return $this->decode($subpart['encoding'],$subpart['body']);
					}
				}
			}
			if($part['type'] == 'text' && $part['subtype'] == 'html') {
				return $this->decode($part['encoding'],$part['body']);
			}
		}
/*		$inlines = array();
		foreach($this->struct['parts'] as $part) {
			if($part["id"] != "") {
				$inlines[] = &$part;
				$part["id"] = substr($part["id"],1,-1);
				//saving to disk
				$ofp = fopen($part["id"], "wb");
				fwrite($ofp,$this->decode($part['encoding'], $part['body']));
				fclose($ofp);
			}

		}*/
		
		return null;
	}

	public function setBody($index='1', $body=NULL) {

		if(count($this->struct['parts']) == 0 && $index == 1) {
			$this->struct['body'] = $body;
			return;
		}
		$tmparr = &$this->struct;
		$deep = explode(".", $index);
		foreach($deep as $i) {
			$i=intval($i);
			$tmparr = &$tmparr['parts'][$i-1];
		}
		$tmparr['body'] = $body;
	}

	public function __set($name, $value) {
		switch ($name) {
			case 'headers':
				$this->parseHeaders($value);
				break;
			case 'structure':
				$this->init_str($value);
				break;
			case 'encoding':
				$this->encoding = $value;
				break;
		}
	}

	public function __get($prop) {
		if($this->structure_initialized) {
			switch ($prop) {
				case 'parts_count':
					return count($this->struct['parts']);
				case "structure": 
					return $this->struct;
//				case "json_structure":
//					$encoded = $this->toHtmlEntities($this->structure);
//					return html_entity_decode(json_encode($encoded), ENT_COMPAT | ENT_HTML5, $this->charset);
				case "attachments":
					$attaches = array();
					$this->attachmentList($this->struct, $attaches);
					return $attaches;
//				case "charset":
//					return $this->charset;
				case "parts": 
					return $this->body_parts_indexes;
				case 'headers':
					if(empty($this->headers)) {
						throw new mimeException("MIME warning: empty headers array\n");
					}
					return $this->headers;
				case 'encoding':
					return $this->encoding;
			}
		}
		else {
			throw new mimeException("MIME error: Getting parameter $prop on uninitialized MIME object\n");
			exit(1);
		}
	}
}


?>