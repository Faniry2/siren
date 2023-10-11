<?php

/*
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */

/**
 * Description of webScrap
 *
 * @author sleco
 */
class WebScrap {
	/*
	$custom = [
		'cookies' 		=> 'name1=content1; name2=content2;',
		'headers' 		=> ['Authorization: Bearer AbCdEfGhIjKlMnOpQ','Content-Type: application/json'],
		'post' 			  => ['firstname' => 'Xavi','lastname' => 'Esteve'],
		'user_agent' 	=> '', // if none set, it will randomize from the list
		'userpass' 		=> 'clark:kent',
	];
	*/
	public function curl( $url, $custom = [] ){
            
            
		$user_agent = [
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/58.0.3029.110 Safari/537.36',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11_1) AppleWebKit/601.2.7 (KHTML, like Gecko) Version/9.0.1 Safari/601.2.7',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_11) AppleWebKit/601.1.56 (KHTML, like Gecko) Version/9.0 Safari/601.1.56',
			'Mozilla/5.0 (Macintosh; Intel Mac OS X 10_10_5) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/50.0.2661.102 Safari/537.36',
			'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36',
			'Mozilla/5.0 (Windows NT 6.1; WOW64; rv:41.0) Gecko/20100101 Firefox/41.0',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/45.0.2454.101 Safari/537.36',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.80 Safari/537.36',
			'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/46.0.2490.71 Safari/537.36',
			'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; rv:11.0) like Gecko',
			'Mozilla/5.0 (Windows NT 6.1; WOW64; Trident/7.0; AS; rv:11.0) like Gecko',
			'Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.13) Gecko/20080311 Firefox/2.0.0.13',
			'Mozilla/5.0 (compatible, MSIE 11, Windows NT 6.3; Trident/7.0; rv:11.0) like Gecko',
			'Mozilla/5.0 (compatible; MSIE 10.0; Windows NT 6.1; Trident/5.0)',
		];
	 	// http://php.net/manual/en/function.curl-setopt.php
		$options = [
			CURLOPT_RETURNTRANSFER => true, 	// return web page
			CURLOPT_HEADER         => true, 	//return headers in addition to content
			CURLOPT_FOLLOWLOCATION => true, 	// follow redirects
			CURLOPT_ENCODING       => "", 		// handle all encodings
			CURLOPT_AUTOREFERER    => true, 	// set referer on redirect
			CURLOPT_CONNECTTIMEOUT => 120, 		// timeout on connect
			CURLOPT_TIMEOUT        => 120, 		// timeout on response
			CURLOPT_MAXREDIRS      => 10, 		// stop after 10 redirects
			CURLINFO_HEADER_OUT    => true,
			CURLOPT_SSL_VERIFYPEER => false, 	// Disabled SSL Cert checks
			CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
			CURLOPT_COOKIE         => ( array_key_exists('cookies', $custom) ? $custom['cookies'] : null ),
			CURLOPT_USERAGENT      => ( array_key_exists('user_agent', $custom) ? $custom['user_agent'] : $user_agent[ array_rand($user_agent) ] ),
		];
		// Headers
		if ( array_key_exists('headers', $custom) AND is_array( $custom['headers'] ) ) {
			$options[ CURLOPT_HTTPHEADER ] = $custom['headers'];
		}
		// Post data (put as PHP array, this converts to JSON)
		if ( array_key_exists('post', $custom) AND is_array( $custom['post'] ) ) {
			$options[ CURLOPT_POST ] = true;
			$options[ CURLOPT_POSTFIELDS ] = $custom['post'];
		}
		if ( array_key_exists('userpass', $custom) ) {
			$options[ CURLOPT_USERPWD ] = $custom['userpass'];
		}
		$ch 		= curl_init( $url );
		curl_setopt_array( $ch, $options );
		$rough_content = curl_exec( $ch );
		$err 		= curl_errno( $ch );
		$errmsg 	= curl_error( $ch );
		$header 	= curl_getinfo( $ch );
		curl_close( $ch );
		$header_content = substr( $rough_content, 0, $header['header_size'] );
		$body_content = trim( str_replace( $header_content, '', $rough_content ) );
		preg_match_all( "#Set-Cookie:\\s+(?<cookie>[^=]+=[^;]+)#m", $header_content, $matches );
		$cookiesOut = implode( "; ", $matches['cookie'] );
		$header['errno'] 	= $err;
		$header['errmsg'] 	= $errmsg;
		$header['headers'] 	= $header_content;
		$header['content'] 	= $body_content;
		$header['cookies'] 	= $cookiesOut;
                
                
		return $header;
	}
        
        
        
        
	public function regex( $regex, $string )
	{
		// regex flags: http://php.net/manual/en/reference.pcre.pattern.modifiers.php
		preg_match_all(
		  $regex,
			$string,
			$matches,
			PREG_SET_ORDER // formats data into an array of items
		);
		return $matches;
	}
}
