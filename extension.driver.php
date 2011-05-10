<?php
	
	Class Extension_Highrise extends Extension {
	
		private $url;
		private $token;
		
		private $save;
	
		public function __construct(Array $args) {
			parent::__construct($args);
			$config = Symphony::Configuration();
			
			$this->url = $config->get('url','highrise');
			$this->token = $config->get('token','highrise');
			$this->save = $config->get('save','highrise');
		}
	
		public function about() {
			return array(
				'name' => 'Highrise',
				'version' => '0.1a',
				'release-date' => '2011-04-20',
				'author' => array(
					'name' => 'Willy',
					'website' => 'http://1009design.com/',
					'email' => 'willy@1009design.com'
				),
				'description' => 'push'
			);
		}
	
		public function getSubscribedDelegates() {
			return array(
				//註冊欄位到prefrence
				array(
					'page' => '/system/preferences/',
					'delegate' => 'AddCustomPreferenceFieldsets',
					'callback' => 'appendPreferences'
				),
				//儲存prefrence時的動作
				array(
					'page' => '/system/preferences/',
					'delegate' => 'Save',
					'callback' => '__SavePreferences'
				),
				//
				array(
					'page' => '/frontend/',
					'delegate' => 'EventPreSaveFilter',
					'callback' => 'eventPreSaveFilter'
				),
				array(
					'page' => '/frontend/',
					'delegate' => 'EventPostSaveFilter',
					'callback' => 'eventPostSaveFilter'
				),
				array(
					'page' => '/blueprints/events/new/',
					'delegate' => 'AppendEventFilter',
					'callback' => 'appendEventFilter'
				),
				array(
					'page' => '/blueprints/events/edit/',
					'delegate' => 'AppendEventFilter',
					'callback' => 'appendEventFilter'
				)
			);
		}
		
		/**
		 * Append highrise mode preferences
		 *
		 * @param array $context
		 *  delegate context
		 */
		public function appendPreferences($context) {

			// Create preference group
			$group = new XMLElement('fieldset');
			$group->setAttribute('class', 'settings');
			$group->appendChild(new XMLElement('legend', __('Highrise')));
			
			$config = Symphony::Configuration();
			
			// Append settings
			$text = $config->get('save-entry','highrise');
			$saveInput = Widget::Input('settings[highrise][save-entry]', 'yes', 'checkbox');
			if(empty($text) || $text == 'yes') {
				$saveInput->setAttribute('checked', 'checked');
			}
			$saveLabel = Widget::Label($saveInput->generate() . ' ' . __('Continue save data'));
			$saveHelp = new XMLElement('p', __('api token can be found under My Info'), array('class' => 'help'));
			
			$text = $config->get('url','highrise');
			$urlInput = Widget::Input('settings[highrise][url]', empty($text)?'':$text, 'text');
			$urlLabel = Widget::Label('URL:',$urlInput);
			$urlHelp = new XMLElement('p', __('Check the box to continue saving process otherwise stops after eventPreSaveFilter called.'), array('class' => 'help'));
			
			$text = $config->get('token','highrise');
			$tokenInput = Widget::Input('settings[highrise][token]', empty($text)?'':$text, 'text');
			$tokenLabel = Widget::Label('Token:',$tokenInput);
			$tokenHelp = new XMLElement('p', __('e.g. http://yourcompany.highrisehq.com'), array('class' => 'help'));
			
			$group->appendChild($saveLabel);
			$group->appendChild($saveHelp);
			
			$group->appendChild($urlLabel);
			$group->appendChild($urlHelp);
			
			$group->appendChild($tokenLabel);
			$group->appendChild($tokenHelp);
			
			// Append new preference group			
			$context['wrapper']->appendChild($group);
		}	
		
		/**
		 * Save preferences
		 *
		 * @param array $context
		 *  delegate context
		 */
		public function __SavePreferences($context) {

			if(!is_array($context['settings'])) {
				$context['settings'] = array('highrise' => array('url' => '', 'token' => '', 'save-entry'=>'no'));
			} elseif(!isset($context['settings']['highrise']['save-entry'])) {
				$context['settings']['highrise']['save-entry'] = 'no';
			}
			
		}
		
		public function eventPreSaveFilter($context) {
			if($this->save == 'no') {
				$this->_save($context);
				
				$context['messages'][] = array(
					'Highrise', FALSE, __('prevents from saving to section')
				);
			}
		}
		
		public function eventPostSaveFilter($context) {
			
			//
			//	check saving result
			//	$success = $this->_checkSave()
			//
			$success = true;
			
			if($success) {
				$this->_save($context);
			}
		}
		
		private function _save($context) {
			include EXTENSIONS . '/highrise/lib/push2Highrise.php';
			
			if( !isset($_POST['type']) ) {
				$_POST['type'] = '';
			}
			
			$p = new Push_Highrise($this->url,$this->token);
			switch($_POST['type']) {
				case 'pushDeal':
					$p->pushDeal($_POST);
					break;
				case 'pushNote':
					$p->pushNote($_POST);
					break;
				case 'pushTask':
					$p->pushTask($_POST);
					break;
				case 'pushContact':
					$p->pushContact($_POST);
					break;
				default:
					//do nothing
					break;
			}
			return false;
		}
		
		public function appendEventFilter($context) {
			$context['options'][] = array(
				'highrise-create-user',
				@in_array('highrise-create-user', $context['selected']),
				'Highrise: Create User	'
			);
		}
	}