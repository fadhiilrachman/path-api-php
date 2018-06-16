<?php

/**
* Path Private API PHP
* @author Fadhiil Rachman <https://www.instagram.com/fadhiilrachman>
* @version 0.0.3
* @license https://github.com/fadhiilrachman/path-api-php/blob/master/LICENSE The MIT License
*/

namespace FadhiilRachman\Path;

use \FadhiilRachman\Path\Constants;
use \FadhiilRachman\Path\PathException;

class Path extends PathException
{
	protected $email;
	protected $password;
	protected $isLoggedIn=false;

	public $user_id;
	public $user_token;
	public $pathData;
	
	protected $boundary;
	protected $header=[];
	
	function __construct($email, $password)
	{
		$this->email = $email;
		$this->password = $password;

		$this->pathData = __DIR__ . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;

		if (!file_exists( $this->pathData )) {
			mkdir($this->pathData , 0777);
		}

		if ((file_exists($this->pathData."$this->email-userId.log")) && (file_exists($this->pathData."$this->email-token.log"))) {

			$this->isLoggedIn = true;
			$this->user_id = trim(file_get_contents($this->pathData."$this->email-userId.log"));
			$this->user_token = trim(file_get_contents($this->pathData."$this->email-token.log"));

		}
	}

	public function login($relogin=false) {
		if(!$this->isLoggedIn || $relogin==true) {
			$data=[
				"login"				=> $this->email,
				"password"			=> $this->password,
				"reactivate_user"		=> 1,
				"client_id"			=> Constants::CLIENT_ID
			];
			$formBody=$this->buildBody($data);
			$login = $this->request("/3/user/authenticate", 0, $formBody);
			if( isset($login['id']) && isset($login['oauth_token']) ) {
				$this->isLoggedIn = true;
				$this->user_id = $login['id'];
				$this->user_token = $login['oauth_token'];
				file_put_contents($this->pathData."$this->email-userId.log", $this->user_id);
				file_put_contents($this->pathData."$this->email-token.log", $this->user_token);
				return true;
			} else {
				throw new PathException($login['display_message'] . " (". $login['error_type'] .")", $login['error_code']);
				return ;
			}
			return $login;
		} else {
			return true;
		}
	}

	public function addFriendRequest($user_id=array()) {
		if(!$this->isLoggedIn) {
			throw new PathException('Your are not logged in', 400);
			return ;
		}
		$data=[
			"user_ids"				=> $user_id,
			"oauth_token"				=> $this->user_token,
		];
		$formBody=$this->buildBody($data);
		$request = $this->request("/3/friend_request/add", 0, $formBody);
		if( isset($request['error_code']) ) {
			throw new PathException($request['display_message'] . " (". $request['error_type'] .")", $request['error_code']);
			return ;
		}
		return $request;
	}

	public function momentAddEmotion($moment_id,$emo_type,$emo_extra=false,$emotions_with_comments=true,$location=false) {
		if(!$this->isLoggedIn) {
			throw new PathException('Your are not logged in', 400);
			return ;
		}
		switch ($emo_type) {
			case '1':
				$emo_type="happy";
				break;
			case '2':
				$emo_type="laugh";
				break;
			case '3':
				$emo_type="surprise";
				break;
			case '4':
				$emo_type="sad";
				break;
			case '5':
				$emo_type="love";
				break;
			
			default:
				$emo_type=$emo_type;
				break;
		}
		$data=[
			"moment_id"				=> $moment_id,
			"emotion_type"				=> $emo_type,
			"extra_emotions"			=> $emo_extra,
			"emotions_with_comments"		=> $emotions_with_comments,
			"oauth_token"				=> $this->user_token,
		];
		if ($location) {
			array_push($data,
			["location"	=> [
				"lat"		=> $location["latitude"],
				"lng"		=> $location["longitude"],
				"distance"	=> $location["distance"]
			]]);
		}
		$formBody=$this->buildBody($data);
		$request = $this->request("/3/moment/emotion/add", 0, $formBody);
		if( isset($request['error_code']) ) {
			throw new PathException($request['display_message'] . " (". $request['error_type'] .")", $request['error_code']);
			return ;
		}
		return $request;
	}

	public function feedHome($limit, $emotions_with_comments=true, $newer_than=false) {
		if(!$this->isLoggedIn) {
			throw new PathException('Your are not logged in', 400);
			return ;
		}
		$data=[
			'limit'					=>	"$limit",
			'gs'					=>	'1',
			'emotions_with_comments'		=>	'true',
			'connection_type'			=>	'WIFI',
			'oauth_token'				=>	$this->user_token,
			'newer_than'				=>	"$newer_than",
		];
		$request = $this->request("/4/moment/feed/home", $data, 0);
		if( isset($request['error_code']) ) {
			throw new PathException($request['display_message'] . " (". $request['error_type'] .")", $request['error_code']);
			return ;
		}
		return $request;
	}

	protected function buildBody($data, $boundary=false) {
		if(!$boundary) {
			$this->boundary=mt_rand(10000,mt_getrandmax());
		} else {
			$this->boundary=$boundary;
		}
		$body = '';
		array_push($this->header, 'Content-Type: multipart/form-data; boundary='.$this->boundary);
		
		$body .= '--'.$this->boundary."\r\n";
		$body .= 'Content-Disposition: form-data; name="post"'."\r\n";
		$body .= 'Content-Type: text/plain; charset=UTF-8'."\r\n";
		$body .= 'Content-Transfer-Encoding: 8bit'."\r\n";

		$body .= "\r\n\r\n".json_encode($data, true)."\r\n";
		$body .= '--'.$this->boundary.'--';

		return $body;
	}

	protected function request($endpoint, $param=false, $post=false) {
		$curl = curl_init();
		array_push($this->header, 'X-PATH-VERSION-CODE: ' . Constants::PATH_VERSION);
		array_push($this->header, 'X-PATH-TIMEZONE: ' . Constants::PATH_TIMEZONE);
		array_push($this->header, 'X-PATH-REQUEST-ID: A_'.mt_rand(10000,mt_getrandmax()).'_'.mt_rand(1,10));
		curl_setopt($curl, CURLOPT_USERAGENT, Constants::USER_AGENT);
		curl_setopt_array($curl, array(
			CURLOPT_URL			=> Constants::API_URL . $endpoint . ( $param ? '?'.http_build_query($param) : ''),
			CURLOPT_HTTPHEADER		=> $this->header,
			CURLOPT_USERAGENT		=> Constants::USER_AGENT,
			CURLOPT_RETURNTRANSFER		=> 1,
			CURLOPT_VERBOSE			=> 0,
			CURLOPT_SSL_VERIFYHOST		=> 0,
			CURLOPT_SSL_VERIFYPEER		=> 0
		));
		if ($post) {
			curl_setopt($curl, CURLOPT_POST, 1);
			curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
		}
		$data = curl_exec($curl);
		if(!$data) {
			throw new PathException('cUrl has been crashed', 500);
			return ;
		}
		curl_close($curl);
		return json_decode($data, true);
	}


}
