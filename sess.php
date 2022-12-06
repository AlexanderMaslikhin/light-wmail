<?php


class iSession {

	const SESS_SIG_KEY = '__sess_sig';
	private static $signature;

	private static function genSessionSignature() {
		$sigstr = implode("::",array($_SERVER['HTTP_USER_AGENT'],$_SERVER['REMOTE_ADDR']));
		 return (hash_hmac("sha256",$sigstr,self::getvar("sess_secret")));
	}

	private static function validate() {
		return self::$signature === $_SESSION[self::SESS_SIG_KEY];
	}

	public static function start() {

			session_start();
//			print_r($_SESSION);
			if( isset( $_SESSION[self::SESS_SIG_KEY] ) ) { //not new session
				self::$signature = self::genSessionSignature();

				if( !self::validate() ) {  //wrong signature, brocken sess

					$err_msg = "Wrong session signature! Try to login again";
					self::destroy();
					die($err_msg);

				}
				if( (time() - self::getvar('sess_date')) >= 86400) {
					//session expired
					self::destroy();
				}
				if( (time() - self::getvar('sessid_date')) >=3600 ) {
					//session id expired
					self::changeId(true);
					self::setvar("sessid_date", time());
				}
				
			}
			else { //new session
				self::changeId();
				self::setvar("sess_secret", random_bytes("10"));
				self::setvar('AUTH_TRY',0);
				self::setvar("sess_date",time());
				self::setvar("sessid_date", time());
				self::$signature = self::genSessionSignature();
				self::setvar(self::SESS_SIG_KEY,self::$signature);
		}
	}

	public static function changeId($del_old = false) {
		session_regenerate_id($del_old);
	}

	public static function destroy() {
		session_unset();
		session_destroy();
	}

	public static function setvar($key,$val) {
		$_SESSION[$key] = $val;
	}

	public static function getvar($key) {

		if( isset( $_SESSION[$key]) ) {
			return $_SESSION[$key];
		}
		else return NULL;
	}

	public static function unsetvar($key) {
		unset($_SESSION[$key]);
	}

	public static function isSet($var) {
		return isset($_SESSION[$var]);
	}

	public static function authorized() {
		if( !isset($_SESSION['authorized']) ) return false;
		return self::validate() && $_SESSION['authorized'] && isset($_SESSION['email']);
	}

}

?>