<?php

/**
*	@file cError.php
*	@brief Error logging object.
*/

namespace Error {

/**
*	@brief Error logging object.
*/
class cError {
	private $error_file = null; /**< destination log file */
	private $error_res = null; /**< resource to log file */

	/**	opens a resource to the destination file
	*	@param $error_file
	*/
	function __construct($error_file = null) {
		$this->error_file = $error_file ? $error_file : (defined("LOG_FILE") ? LOG_FILE : null);
		$this->error_res = $this->error_file ? fopen($this->error_file, "a") : null;
	}

	/**	releases the resource to the log file */
	function __destruct() {
		if ($this->error_res) { fclose($this->error_res); $this->error_res = null; }
	}

	/**	logs to destination file
	*	@param $error the error to log
	*	@return nothing
	*/
	function logError($error) {
		$error  = date("r")." --- ".$error."\n\n";

		$error .= "\$_SERVER  ".str_repeat("-", 80)."\n";
		$error .= (isset($_SERVER)  ? print_r($_SERVER, true)  : "\$_SERVER not set" )."\n\n";

		$error .= "\$_SESSION ".str_repeat("-", 80)."\n";
		$error .= (isset($_SESSION) ? print_r($_SESSION, true) : "\$_SESSION not set")."\n\n";

		$error .= "\$_COOKIE  ".str_repeat("-", 80)."\n";
		$error .= (isset($_COOKIE)  ? print_r($_COOKIE, true)  : "\$_COOKIE not set" )."\n\n";

		$error .= "\$_REQUEST ".str_repeat("-", 80)."\n";
		$error .= (isset($_REQUEST) ? print_r($_REQUEST, true) : "\$_REQUEST not set")."\n\n";

		$error .= "\$_POST    ".str_repeat("-", 80)."\n";
		$error .= (isset($_POST)    ? print_r($_POST, true)    : "\$_POST not set"   )."\n\n";

		$error .= "\$_GET     ".str_repeat("-", 80)."\n";
		$error .= (isset($_GET)     ? print_r($_GET, true)     : "\$_GET not set"    )."\n\n";

		$error .=  "backtrace ".str_repeat("-", 80)."\n";
		$error .= print_r(debug_backtrace(), true)."\n\n";

		if ($this->error_res)
			fwrite($this->error_res, $error);

		if (defined("ERROR_LOG_EMAIL_ADDRESS"))
			mail(ERROR_LOG_EMAIL_ADDRESS, "error logged ({$_SERVER['SCRIPT_NAME']})", $error, "", "-f".ERROR_LOG_EMAIL_ADDRESS);
	}
}

}

?>
