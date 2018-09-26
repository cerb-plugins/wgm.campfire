<?php
if(class_exists('Extension_PluginSetup')):
class WgmCampfire_Setup extends Extension_PluginSetup {
	const POINT = 'wgmcampfire.setup';
	
	function render() {
		$tpl = DevblocksPlatform::services()->template();

		$params = array(
			'api_token' => DevblocksPlatform::getPluginSetting('wgm.campfire','api_token',''),
			'url' => DevblocksPlatform::getPluginSetting('wgm.campfire','url',''),
		);
		$tpl->assign('params', $params);
		
		$tpl->display('devblocks:wgm.campfire::setup/index.tpl');
	}
	
	function save(&$errors) {
		try {
			@$api_token = DevblocksPlatform::importGPC($_REQUEST['api_token'],'string','');
			@$url = DevblocksPlatform::importGPC($_REQUEST['url'],'string','');
			
			if(empty($api_token) || empty($url))
				throw new Exception("Both the API Auth Token and URL are required.");
				
			$campfire = WgmCampfire_API::getInstance();
			$campfire->setCredentials($api_token, $url);
			$response = $campfire->request('rooms.json', null);
			$response = json_decode($response);
			
			if($response == '')
				throw new Exception("There was a problem connecting! Does the user have access to any rooms?");
				
			$rooms = array();
			foreach($response->rooms as $room) {
				$rooms[$room->id] = $room->name;
			}
			$rooms = json_encode($rooms);
			
			DevblocksPlatform::setPluginSetting('wgm.campfire','api_token',$api_token);
			DevblocksPlatform::setPluginSetting('wgm.campfire','url',$url);
			DevblocksPlatform::setPluginSetting('wgm.campfire','rooms', $rooms);

			return true;
			
		} catch (Exception $e) {
			$errors[] = $e->getMessage();
			return false;
		}
	}
};
endif;

class WgmCampfire_API {
	static $_instance = null;
	private $_api_token = null;
	private $_url = null;
	
	private function __construct() {
		$this->_api_token = DevblocksPlatform::getPluginSetting('wgm.campfire','api_token','');
		$this->_url = DevblocksPlatform::getPluginSetting('wgm.campfire','url','');
	}
	
	/**
	 * @return WgmCampfire_API
	 */
	static public function getInstance() {
		if(null == self::$_instance) {
			self::$_instance = new WgmCampfire_API();
		}

		return self::$_instance;
	}
	
	public function setCredentials($api_token, $subdomain) {
		$this->_api_token = $api_token;
		$this->_url = $subdomain;
	}
	
	/**
	 *
	 * @param string $path
	 * @param string $post
	 * @return mixed
	 */
	public function request($path, $post) {
		$url = sprintf('https://%s.campfirenow.com/%s', $this->_url, $path);
		$ch = DevblocksPlatform::curlInit();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:X', $this->_api_token));
		curl_setopt($ch, CURLOPT_HEADER, false);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
		if($post !== null) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
		}
		
		$response = DevblocksPlatform::curlExec($ch);
		curl_close($ch);
		return $response;
	}
};

class WgmCampfire_EventActionPost extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::services()->template();
		$tpl->assign('params', $params);
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
			
		$rooms = DevblocksPlatform::getPluginSetting('wgm.campfire', 'rooms', '');
		// get rooms as an array
		$rooms = json_decode($rooms, TRUE);
		$tpl->assign('rooms', $rooms);
		
		$tpl->display('devblocks:wgm.campfire::events/action_post_campfire.tpl');
	}
	
	function simulate($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$rooms = DevblocksPlatform::getPluginSetting('wgm.campfire', 'rooms', '');
		$rooms = json_decode($rooms, TRUE);
		
		$out = '';
		
		@$room = $params['room'];
		
		if(empty($room))
			return "[ERROR] No room is defined.";
		
		if(!isset($rooms[$room]))
			return "[ERROR] Selected room is invalid.";
		
		// [TODO] Test API
		
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		if(false !== ($content = $tpl_builder->build($params['content'], $dict))) {
			$out .= sprintf(">>> Posting to Campfire (%s):\n%s\n",
				$rooms[$room],
				$content
			);
		}
		
		return $out;
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, DevblocksDictionaryDelegate $dict) {
		$campfire = WgmCampfire_API::getInstance();

		// Translate message tokens
		$tpl_builder = DevblocksPlatform::services()->templateBuilder();
		if(false !== ($content = $tpl_builder->build($params['content'], $dict))) {
			$path = sprintf('room/%d/speak.json', $params['room']);
			
			if(@$params['is_paste']) {
				$messages = array($content);
			} else {
				$messages = DevblocksPlatform::parseCrlfString($content);
			}

			if(is_array($messages))
			foreach($messages as $message) {
				$data = array(
					'message' => array(
						'body' => $message,
					),
				);
				
				$json = json_encode($data);
				$response = $campfire->request($path, $json);
			}
		}
	}
};