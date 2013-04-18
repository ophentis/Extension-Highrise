<?php
/**
 *	The 
 *
 *
 */
class Push_Highrise{

	var $highrise_url = ''; 									// your highrise url, e.g. http://yourcompany.highrisehq.com
	var $api_token = ''; 										// your highrise api token; can be found under My Info
	var $task_assignee_user_id = ''; 							// user id of the highrise user who gets the task assigned 
	var $category = ''; 										// the category where deals will be assigned to
	
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
	
	public function getApiToken() {
		return $this->api_token;
	}
	
	protected function setErrorMsg($msg) {
		$this->errorMsg = $msg;
	}
	
	public function getErrorMsg() {
		return $this->errorMsg;
	}
	
	public function pushDeal($request) {
		
		$path = '/deals.xml';
		$content = '<deal>
			<name>'.htmlspecialchars($request['subject']).'</name>
			<price-type>fixed</price-type>
			<category-id type="integer">'.$category.'</category-id>
			<responsible-party-id type="integer">'.$this->task_assignee_user_id.'</responsible-party-id>
			<background>'.htmlspecialchars($request['note']).'</background>
			<visible-to>Everyone</visible-to>
			<party-id type="integer">'.$this->getContactId($request).'</party-id>
		</deal>';
		
		$response = $this->_post($path,$content);
		return $response;
	}
	
	public function pushNote($request) {
		$path = '/notes.xml';
		
		$content = '<note>
			<subject-id type="integer">'.@$request['id'].'</subject-id>
			<subject-type>'.@$request['type'].'</subject-type>
			<body>'.@htmlspecialchars($request['note']).'</body>
		</note>';
		
		$response = $this->_post($path,$content);
		return $response;
	}
	
	public function pushTask($request) {
		$path = '/tasks.xml';
		
		$bodyPrefix = 'Task subject'; // set the subject
		$content = '<task>
			<subject-id type="integer">'.$this->getContactId($request).'</subject-id>
			<subject-type>Party</subject-type>
			<body>'.$bodyPrefix.' '.@htmlspecialchars($request['subject']).' - '.@htmlspecialchars($request['note']).'</body>
			<frame>today</frame>
			<public type="boolean">true</public>
			<owner-id type="integer">'.$this->task_assignee_user_id.'</owner-id>
		</task>';
	
		$response = $this->_post($path,$content);
		return $response;
	}
	
	/**
	 *	Create a new contact
	 *	@return false if member exist
	 */
	public function pushContact($request, $custom='') {
		$path = '/people.xml';
		$content = '<person>
			<first-name>'.@htmlspecialchars($request['firstname']).'</first-name>
			<last-name>'.@htmlspecialchars($request['lastname']).'</last-name>
			<background>'.@htmlspecialchars($request['background']).'</background>
			<company-name>'.@htmlspecialchars($request['company']).'</company-name>
			<contact-data>
				<email-addresses>
					<email-address>
						<address>'.@htmlspecialchars($request['email']).'</address>
						<location>'.@htmlspecialchars($request['email_location']).'</location>
					</email-address>
				</email-addresses>
			<phone-numbers>
				<phone-number>
					<number>'.@htmlspecialchars($request['phone']).'</number>
					<location>'.@htmlspecialchars($request['phone_location']).'</location>
				</phone-number>
			</phone-numbers>
			<addresses>
				<address>
				  <city>'.@htmlspecialchars($request['city']).'</city>
				  <country>'.@htmlspecialchars($request['country']).'</country>
				  <state>'.@htmlspecialchars($request['state']).'</state>
				  <street>'.@htmlspecialchars($request['street']).'</street>
				  <zip>'.@htmlspecialchars($request['zip']).'</zip>
				  <location>Work</location>
				</address>
			  </addresses>
			</contact-data>
			'.$custom.'
		</person>';
		
		$response = $this->_post($path,$content);
		return $response;
	}
	

	/**
     * Creates new custom fields in highrise
     */
	public function pushCustom($custom=array())
	{
		$path = '/subject_fields.xml';
		foreach ($custom as $v)
		{			
			$content = '<subject-field>
				<label>'.@htmlspecialchars($v).'</label>
				</subject-field>
				';
			// this is pretty bad
			// the api doens't let you submit a batch of custom fields
			// so i'm stuck doing one at a time. hope for no errors!
			$response = $this->_post($path, $content);
		}
	}


	/**
     * Gets the id of a custom field
     * @return The id of the custom field passed.
     */
	public function getCustomId($field, $page=1) {
		$path = '/subject_fields.xml';

		$query = array('n'=>($page-1)*25);
		
		$xml = $this->_get($path,$query);
		
		if(!$xml) {
			return false;
		}
		
		// parse XML
		$people = simplexml_load_string($xml);
		

		foreach ($people->{'subject-field'} as $label)
		{
			// If there is a match return the id of the custom field
			if ($field == $label->label) 
				return $label->id;
		}
		return false;
	}

	/**
	 *	Search for a person in Highrise
	 *	Change this function if using more or less check
	 *
	 */
	public function getContactId($person) {
		$path = '/people/search.xml';
		if(isset($person['email'])) {
			$query = array('criteria[email]'=>$person['email']);
		} else {
			$query = array('term' => urlencode($person['firstname'].' '.$person['lastname']));
		}
		
		$xml = $this->_get($path,$query);
		
		if(!$xml) {
			return false;
		}
		
		//Parse XML
		$people = simplexml_load_string($xml);
		
		$id = ($people && isset($people->person[0])) ? $id = $people->person[0]->id : '-1';
		return $id;
	}


	/**
	 * Creates the xml needed for custom fields
     * @return A string that contains the custom xml values to be submitted.
     */
	public function createCustomXml($input=array(), $custom=array())
    {
        $customXml = '<subject_datas type="array">';
        foreach ($input as $k => $v)
        {
            if (array_key_exists($k, $custom) && !empty($v))
            {
                $customXml .= '<subject_data>
                      <value>'.@htmlspecialchars($v).'</value>
                      <subject_field_id type="integer">'.@htmlspecialchars($this->getCustomId($custom[$k])).'</subject_field_id>
                    </subject_data>
                    ';
            }
        }
        $customXml .= '</subject_datas>';
        return $customXml;
    }

    /**
     * Pass a key value with the value being the name 
     * 	custom field you want to create in highrise.
     */
    public function createCustomFields($custom)
    {
    	$fields = array();
		foreach ($custom as $k => $v)
		{
		    array_push($fields, $v);
		}
		$this->pushCustom($fields);
    }
	
	/**
	 *	http://developer.37signals.com/highrise/people
	 *	@return an array containing the person. Empty if no matches
	 */
	public function listContact($page=1) {
		$path = '/people/search.xml';
		$query = array('n'=>($page-1)*25);
		
		$response = $this->_get($path,$query);
		return $response;
	}
	
	/**
	 *	Push a new Case
	 */
	public function pushCase($request) {
		$path = '/kases.xml';
		$content = '<kase>
			<name>'.@htmlspecialchars($request['casename']).'</name>
			<visible-to>Everyone</visible-to>
			<group-id type="integer">'.@htmlspecialchars($request['groupid']).'</group-id>
			<owner-id type="integer">'.@htmlspecialchars($request['ownerid']).'</owner-id>
		</kase>';

		$response = $this->_post($path,$content);
		return $response;
	}
	
	public function listCases() {
		$path = '/kases/open.xml';
		$response = $this->_get($path);
		return $response;
	}
	
	/**
	 *	http://developer.37signals.com/highrise/notes
	 *
	 *	@param $subjectType = ['people' | 'companies' | 'kases' | 'deals']
	 *	@param $subjectId = (stiring) {target id}
	 *	@return xml
	 */
	public function listNote($subjectType, $subjectId,$page=1) {
		$path = '/'.$subjectType.'/'.$subjectId.'/notes.xml';
		$query = array('n'=>(int)($page-1)*25);
		
		$response = $this->_get($path,$query);
		return $response;
	}
	
	public function _get($path,$query=null) {
		$url = $this->highrise_url . $path . '?' . (is_array($query) ? self::createQuery($query) : '');
		//echo $url;
		$curl = curl_init($url);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x'); //Username (api token, fake password as per Highrise api)
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);

		$xml = curl_exec($curl);
		$this->setErrorMsg(curl_error($curl));
		curl_close($curl);
		
		return $xml;
	}
	
	public function _post($path,$content) {
		$url = $this->highrise_url . $path;
		$curl = curl_init($url);
		curl_setopt($curl,CURLOPT_RETURNTRANSFER,true);
		curl_setopt($curl,CURLOPT_USERPWD,$this->api_token.':x'); 
		curl_setopt($curl,CURLOPT_HTTPHEADER,Array('Content-Type: application/xml'));
		curl_setopt($curl,CURLOPT_POST,true);		
		curl_setopt($curl,CURLOPT_SSL_VERIFYPEER,0);
		curl_setopt($curl,CURLOPT_SSL_VERIFYHOST,0);
		curl_setopt($curl,CURLOPT_POSTFIELDS,$content);
		
		$xml = curl_exec($curl);
		$this->setErrorMsg(curl_error($curl));
		curl_close($curl);
		
		return $xml;
	}
	
	public static function createQuery($arr) {
		$query = array();
		foreach($arr as $k => $v) {
			$query[] = $k . '=' . $v;
		}
		return implode('&',$query);
	}
}