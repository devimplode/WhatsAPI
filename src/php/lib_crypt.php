<?php
	class crypt {
		public static function _genSecret($pass,$len){
			if(strlen($pass)<15)
				$pass=md5($pass).$pass.sha1($pass);
			if($len>strlen($pass))
				$pass=str_repeat($pass,intval(ceil($len/strlen($pass))));
			if(strlen($pass)>$len)
				$pass=substr($pass,0,$len);
			return $pass;
		}
		public static function encrypt($string="",$pass=""){
			$pass=self::_genSecret($pass,strlen($string));
			return(base64_encode($string ^ $pass));
		}
		public static function decrypt($string="",$pass=""){
			$string=base64_decode($string);
			$pass=self::_genSecret($pass,strlen($string));
			return($string ^ $pass);
		}
	}
