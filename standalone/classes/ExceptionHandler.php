<?php

class ExceptionHandler {
	public function error($ERROR) {
		/*
			Code Table:
			10	-	Force Session Reset
			11  -   API Auth Error
			12  -   API General Error
			100	-	Unknown Action - Just display
		*/
		
		switch ($ERROR->getCode()) {
			case 10:
				if (isset($_COOKIE["zbxwallboard_pw_crypt_key"])) {
					unset($_COOKIE["zbxwallboard_pw_crypt_key"]);
					setcookie('zbxwallboard_pw_crypt_key', null, -1, '/');
				}
				session_destroy();
				break;
		}
		
		$WALLBOARD = new Wallboard();
		$WALLBOARD->gen_menu(array(),array());
		$WALLBOARD->error($ERROR->getCode(),$ERROR->getMessage(),$ERROR->getTraceAsString());
		$WALLBOARD->publish_content();
		
		exit;
	}
}

?>