<?php
if(class_exists('Extension_PluginSetup')):
class WgmCampfire_Setup extends Extension_PluginSetup {
	const POINT = 'wgmcampfire.setup';
	
	function render() {
		$tpl = DevblocksPlatform::getTemplateService();

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
	 * @return HTTPResponse
	 */
	public function request($path, $post) {
		$url = sprintf('https://%s.campfirenow.com/%s', $this->_url, $path); 	
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_USERPWD, sprintf('%s:X', $this->_api_token));
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-type: application/json'));
		if($post !== null) {
			curl_setopt($ch, CURLOPT_POST, true);
			curl_setopt($ch, CURLOPT_POSTFIELDS, $post);	
		}
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		
		$response = curl_exec($ch);
		curl_close($ch);
		return $response;
	}
};

if(class_exists('Extension_DevblocksEventAction')):
class WgmCampfire_EventActionPost extends Extension_DevblocksEventAction {
	function render(Extension_DevblocksEvent $event, Model_TriggerEvent $trigger, $params=array(), $seq=null) {
		$tpl = DevblocksPlatform::getTemplateService();
		$tpl->assign('params', $params);
		$tpl->assign('token_labels', $event->getLabels());
		
		if(!is_null($seq))
			$tpl->assign('namePrefix', 'action'.$seq);
			
		$rooms = DevblocksPlatform::getPluginSetting('wgm.campfire', 'rooms', '');
		// get rooms as an array
		$rooms = json_decode($rooms, TRUE);
		$tpl->assign('rooms', $rooms);
		
		$tpl->display('devblocks:wgm.campfire::events/action_post_campfire.tpl');
	}
	
	function run($token, Model_TriggerEvent $trigger, $params, &$values) {
		$campfire = WgmCampfire_API::getInstance();

		// Translate message tokens
		$tpl_builder = DevblocksPlatform::getTemplateBuilder();
		if(false !== ($content = $tpl_builder->build($params['content'], $values))) {
			//$path = sprintf('room/%d/join.json', $params['room']);
			//$campfire->request($path, '');
			$path = sprintf('room/%d/speak.json', $params['room']);
			$data = '{"message":{"body":"' . addslashes($content) . '"}}';
			$response = $campfire->request($path, $data);
			//$path = sprintf('room/%d/leave.json', $params['room']);
			//$campfire->request($path, '');
		}
	}
};
endif;
