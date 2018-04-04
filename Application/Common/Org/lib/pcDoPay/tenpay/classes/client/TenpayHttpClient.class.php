<?php

/**
 * http¡¢httpsÍ¨ÐÅÀà
 * ============================================================================
 * apiËµÃ÷£º
 * setReqContent($reqContent),ÉèÖÃÇëÇóÄÚÈÝ£¬ÎÞÂÛpostºÍget£¬¶¼ÓÃget·½Ê½Ìá¹©
 * getResContent(), »ñÈ¡Ó¦´ðÄÚÈÝ
 * setMethod($method),ÉèÖÃÇëÇó·½·¨,post»òÕßget
 * getErrInfo(),»ñÈ¡´íÎóÐÅÏ¢
 * setCertInfo($certFile, $certPasswd, $certType="PEM"),ÉèÖÃÖ¤Êé£¬Ë«ÏòhttpsÊ±ÐèÒªÊ¹ÓÃ
 * setCaInfo($caFile), ÉèÖÃCA£¬¸ñÊ½Î´pem£¬²»ÉèÖÃÔò²»¼ì²é
 * setTimeOut($timeOut)£¬ ÉèÖÃ³¬Ê±Ê±¼ä£¬µ¥Î»Ãë
 * getResponseCode(), È¡·µ»ØµÄhttp×´Ì¬Âë
 * call(),ÕæÕýµ÷ÓÃ½Ó¿Ú
 * 
 * ============================================================================
 *
 */

class TenpayHttpClient {
	//ÇëÇóÄÚÈÝ£¬ÎÞÂÛpostºÍget£¬¶¼ÓÃget·½Ê½Ìá¹©
	var $reqContent;
	//Ó¦´ðÄÚÈÝ
	var $resContent;
	//ÇëÇó·½·¨
	var $method;
	
	//Ö¤ÊéÎÄ¼þ
	var $certFile;
	//Ö¤ÊéÃÜÂë
	var $certPasswd;
	//Ö¤ÊéÀàÐÍPEM
	var	$certType;
	
	//CAÎÄ¼þ
	var $caFile;
	
	//´íÎóÐÅÏ¢
	var $errInfo;
	
	//³¬Ê±Ê±¼ä
	var $timeOut;
	
	//http×´Ì¬Âë
	var $responseCode;
	
	function __construct() {
		$this->TenpayHttpClient();
	}
	
	
	function TenpayHttpClient() {
		$this->reqContent = "";
		$this->resContent = "";
		$this->method = "post";

		$this->certFile = "";
		$this->certPasswd = "";
		$this->certType = "PEM";
		
		$this->caFile = "";
		
		$this->errInfo = "";
		
		$this->timeOut = 120;
		
		$this->responseCode = 0;
		
	}
	
	
	//ÉèÖÃÇëÇóÄÚÈÝ
	function setReqContent($reqContent) {
		$this->reqContent = $reqContent;
	}
	
	//»ñÈ¡½á¹ûÄÚÈÝ
	function getResContent() {
		return $this->resContent;
	}
	
	//ÉèÖÃÇëÇó·½·¨post»òÕßget	
	function setMethod($method) {
		$this->method = $method;
	}
	
	//»ñÈ¡´íÎóÐÅÏ¢
	function getErrInfo() {
		return $this->errInfo;
	}
	
	//ÉèÖÃÖ¤ÊéÐÅÏ¢
	function setCertInfo($certFile, $certPasswd, $certType="PEM") {
		$this->certFile = $certFile;
		$this->certPasswd = $certPasswd;
		$this->certType = $certType;
	}
	
	//ÉèÖÃCa
	function setCaInfo($caFile) {
		$this->caFile = $caFile;
	}
	
	//ÉèÖÃ³¬Ê±Ê±¼ä,µ¥Î»Ãë
	function setTimeOut($timeOut) {
		$this->timeOut = $timeOut;
	}
	
	//Ö´ÐÐhttpµ÷ÓÃ
	function call() {
		//Æô¶¯Ò»¸öCURL»á»°
		$ch = curl_init();

		// ÉèÖÃcurlÔÊÐíÖ´ÐÐµÄ×î³¤ÃëÊý
		curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeOut);

		// »ñÈ¡µÄÐÅÏ¢ÒÔÎÄ¼þÁ÷µÄÐÎÊ½·µ»Ø£¬¶ø²»ÊÇÖ±½ÓÊä³ö¡£
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);

		// ´ÓÖ¤ÊéÖÐ¼ì²éSSL¼ÓÃÜËã·¨ÊÇ·ñ´æÔÚ
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 1);
				
		
		$arr = explode("?", $this->reqContent);
		if(count($arr) >= 2 && $this->method == "post") {
			//·¢ËÍÒ»¸ö³£¹æµÄPOSTÇëÇó£¬ÀàÐÍÎª£ºapplication/x-www-form-urlencoded£¬¾ÍÏñ±íµ¥Ìá½»µÄÒ»Ñù¡£
			curl_setopt($ch, CURLOPT_POST, 1);
			curl_setopt($ch, CURLOPT_URL, $arr[0]);
			//Òª´«ËÍµÄËùÓÐÊý¾Ý
			curl_setopt($ch, CURLOPT_POSTFIELDS, $arr[1]);
		
		}else{
			curl_setopt($ch, CURLOPT_URL, $this->reqContent);
		}
		
		//ÉèÖÃÖ¤ÊéÐÅÏ¢
		if($this->certFile != "") {
			curl_setopt($ch, CURLOPT_SSLCERT, $this->certFile);
			curl_setopt($ch, CURLOPT_SSLCERTPASSWD, $this->certPasswd);
			curl_setopt($ch, CURLOPT_SSLCERTTYPE, $this->certType);
		}
		
		//ÉèÖÃCA
		if($this->caFile != "") {
			// ¶ÔÈÏÖ¤Ö¤ÊéÀ´Ô´µÄ¼ì²é£¬0±íÊ¾×èÖ¹¶ÔÖ¤ÊéµÄºÏ·¨ÐÔµÄ¼ì²é¡£1ÐèÒªÉèÖÃCURLOPT_CAINFO
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 1);
			curl_setopt($ch, CURLOPT_CAINFO, $this->caFile);
		} else {
			// ¶ÔÈÏÖ¤Ö¤ÊéÀ´Ô´µÄ¼ì²é£¬0±íÊ¾×èÖ¹¶ÔÖ¤ÊéµÄºÏ·¨ÐÔµÄ¼ì²é¡£1ÐèÒªÉèÖÃCURLOPT_CAINFO
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
		}
		
		// Ö´ÐÐ²Ù×÷
		$res = curl_exec($ch);
		$this->responseCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
		
		if ($res == NULL) { 
		   $this->errInfo = "call http err :" . curl_errno($ch) . " - " . curl_error($ch) ;
		   curl_close($ch);
		   return false;
		} else if($this->responseCode  != "200") {
			$this->errInfo = "call http err httpcode=" . $this->responseCode  ;
			curl_close($ch);
			return false;
		}
		
		curl_close($ch);
		$this->resContent = $res;

		
		return true;
	}
	
	function getResponseCode() {
		return $this->responseCode;
	}
	
}
?>