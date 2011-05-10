<?php

class Push_Highrise{

	var $highrise_url = ''; 									// your highrise url, e.g. http://yourcompany.highrisehq.com
	var $api_token = ''; 										// your highrise api token; can be found under My Info
	var $task_assignee_user_id = ''; 							// user id of the highrise user who gets the task assigned 
	var $category = ''; 										// the category where deals will be assigned to
	
	var $errorMsg = '';
	
	var $xml = null;
	
	public function __construct($highriseURL, $apiToken) {
		$this->setHighriseURL($highriseURL);
		$this->setApiToken($apiToken);
	}
	
	public function setHighriseURL($url) {
		$this->highrise_url = $url;
	}
	
	public function getHighriseUrl() {
		return $this->highrise_url;
	}
	
	public function setApiToken($token) {
		$this->api_token = $token;
	}
	
	public function getApiTOken() {
		return $this->api_token;
	}
	
	protected function setErrorMsg($msg) {
		$this->errorMsg = $msg;
	}
	
	public function getErrorMsg() {
		return $this->errorMsg;
	}
	
	public function pushDeal($request){
		
		$path = '/deals.xml';
		$content = '<deal>
			<name>'.htmlspecialchars($request['sSubject']).'</name>
			<price-type>fixed</price-type>
			<category-id type="integer">'.$category.'</category-id>
			<responsible-party-id type="integer">'.$this->task_assignee_user_id.'</responsible-party-id>
			<background>'.htmlspecialchars($request['sNotes']).'</background>
			<visible-to>Everyone</visible-to>
			<party-id type="integer">'.$this->_person_in_highrise($request).'</party-id>
		</deal>';
		
		$this->$xml = $this->_post($path,$content);
		return $xml;
	}
	
	public function pushNote($request){
		$path = '/notes.xml';
		
		$bodyPrefix = "Contact request submitted from website";
		$content = '<note>
			<subject-id type="integer">'.$this->_person_in_highrise($request).'</subject-id>
			<subject-type>Party</subject-type>
			<body>'.$bodyPrefix.' '.htmlspecialchars($request['sSubject']).' - '.htmlspecialchars($request['sNotes']).'</body>
		</note>';
		
		$this->$xml = $this->_post($path,$content);
		
		return $xml;
	}
	
	public function pushTask($request){
		$path = '/tasks.xml';
		
		$bodyPrefix = 'Task subject'; // set the subject
		$content = '<task>
			<subject-id type="integer">'.$this->_person_in_highrise($request).'</subject-id>
			<subject-type>Party</subject-type>
			<body>'.$bodyPrefix.' '.htmlspecialchars($request['sSubject']).' - '.htmlspecialchars($request['sNotes']).'</body>
			<frame>today</frame>
			<public type="boolean">true</public>
			<owner-id type="integer">'.$this->task_assignee_user_id.'</owner-id>
		</task>';
	
		$this->$xml = $this->_post($path,$content);
		return $xml;
	}
	
	/**
	 *	Create a new contact if not existing member, and return the id of member.
	 *	
	 *
	 */
	public function pushContact($request){
		//Check that person doesn't already exist
		
		$id = $this->_person_in_highrise($request);
		if( !$id ) {
			return false;
		}else if($id < 0){
			$path = '/people.xml';
			$content = '<person>
				<first-name>'.htmlspecialchars($request['sFirstName']).'</first-name>
				<last-name>'.htmlspecialchars($request['sLastName']).'</last-name>
				<background>'.htmlspecialchars($request['staff_comment']).'</background>
				<company-name>'.htmlspecialchars($request['sCompany']).'</company-name>
				<contact-data>
					<email-addresses>
						<email-address>
							<address>'.htmlspecialchars($request['sEmail']).'</address>
							<location>Work</location>
						</email-address>
					</email-addresses>
				<phone-numbers>
					<phone-number>
						<number>'.htmlspecialchars($request['sPhone']).'</number>
						<location>Work</location>
					</phone-number>
				</phone-numbers>
				<addresses>
				    <address>
				      <city>'.htmlspecialchars($request['sCity']).'</city>
				      <country>'.htmlspecialchars($request['sCountry']).'</country>
				      <state>'.htmlspecialchars($request['sState']).'</state>
				      <street>'.htmlspecialchars($request['sStreet']).'</street>
				      <zip>'.htmlspecialchars($request['sZip']).'</zip>
				      <location>Work</location>
				    </address>
				  </addresses>
				</contact-data>
			</person>';
			$this->$xml = $this->_post($path,$content);
			//echo $xml;
			if( !$this->$xml ) {
				$this->errorMsg = 'curl failed';
			}
			return $this->$xml;
		
		}else{
			return $id;
		}
	}
	
	//Search for a person in Highrise 
	private function _person_in_highrise($person){
		//$path = '/people/search.xml?term='.urlencode($person['sFirstName'].' '.$person['sLastName']);
		
		$path = '/people/search.xml';
		$query = array('term' => urlencode($person['sFirstName'].' '.$person['sLastName']));
		
		$xml = $this->_get($path,$query);
		
		if(!$xml) {
			return false;
		}
		
		//Parse XML
		$people = simplexml_load_string($xml);
		
		$id = '-1';
		if( isset($people->person[0]) ) {
			$id = $people->person[0]->id;
		}
		
		return $id;
		
	}
	
	/**
	 *	http://developer.37signals.com/highrise/people
	 *	@return an array containing the person. Empty if no matches
	 */
	public function listAll($filter=null,$page=1) {
		$path = '/people/search.xml';
		$query = array_merge(array(),$filter,array('n'=>(int)$page*25);
		$xml = $this->_get($path,$query);
		return array();
	}
	
	private function _get($path,$query=null) {
		$url = $this->highrise_url . $path . '?' . implode('&',$query);
		
		$curl = curl_init($url);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x'); //Username (api token, fake password as per Highrise api)
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		$xml = curl_exec($curl);
		curl_close($curl);
		
		return $xml;
	}
	
}