<?php

	require_once(TOOLKIT . '/class.event.php');

	Class eventHighrise_Contact_Case extends Event{

		const ROOTELEMENT = 'hr_contact_case';

		public $eParamFILTERS = array(
			'campaignmonitor'
		);

		public static function about(){
			return array(
					 'name' => 'Highrise Contact Case',
					 'author' => array(
							'name' => '',
							'website' => '',
							'email' => ''),
					 'version' => '1.0',
					 'release-date' => '2011-05-03T19:24:00+00:00',
					 'trigger-condition' => 'action['.self::ROOTELEMENT.']');
		}

		public static function getSource(){
			return false;
		}

		public static function allowEditorToParse(){
			return false;
		}

		public static function documentation(){
			return '
        <h3>Success and Failure XML Examples</h3>
        <p>When saved successfully, the following XML will be returned:</p>
        <pre class="XML"><code>&lt;newsletter result="success" type="create | edit">
  &lt;message>Entry [created | edited] successfully.&lt;/message>
&lt;/newsletter></code></pre>
        <p>When an error occurs during saving, due to either missing or invalid fields, the following XML will be returned:</p>
        <pre class="XML"><code>&lt;newsletter result="error">
  &lt;message>Entry encountered errors when saving.&lt;/message>
  &lt;field-name type="invalid | missing" />
  ...
&lt;/newsletter></code></pre>
        <p>The following is an example of what is returned if any options return an error:</p>
        <pre class="XML"><code>&lt;newsletter result="error">
  &lt;message>Entry encountered errors when saving.&lt;/message>
  &lt;filter name="admin-only" status="failed" />
  &lt;filter name="send-email" status="failed">Recipient username was invalid&lt;/filter>
  ...
&lt;/newsletter></code></pre>
        <h3>Example Front-end Form Markup</h3>
        <p>This is an example of the form markup you can use on your frontend:</p>
        <pre class="XML"><code>&lt;form method="post" action="" enctype="multipart/form-data">
  &lt;input name="MAX_FILE_SIZE" type="hidden" value="5242880" />
  &lt;label>Name
    &lt;input name="fields[name]" type="text" />
  &lt;/label>
  &lt;label>Emaill address
    &lt;input name="fields[emaill-address]" type="text" />
  &lt;/label>
  &lt;input name="action[newsletter]" type="submit" value="Submit" />
&lt;/form></code></pre>
        <p>To edit an existing entry, include the entry ID value of the entry in the form. This is best as a hidden field like so:</p>
        <pre class="XML"><code>&lt;input name="id" type="hidden" value="23" /></code></pre>
        <p>To redirect to a different location upon a successful save, include the redirect location in the form. This is best as a hidden field like so, where the value is the URL to redirect to:</p>
        <pre class="XML"><code>&lt;input name="redirect" type="hidden" value="http://lsrsports.com/success/" /></code></pre>
        <h3>Campaign Monitor Filter</h3>
        <p>
        To use the Campaign Monitor filter, add the following field to your form:
      </p>
        <pre class="XML"><code>&lt;input name="campaignmonitor[list]" value="{$your-list-id}" type="hidden" />
&lt;input name="campaignmonitor[field][Name]" value="$field-first-name, $field-last-name" type="hidden" />
&lt;input name="campaignmonitor[field][Email]" value="$field-email-address" type="hidden" />
&lt;input name="campaignmonitor[field][Custom]" value="Value for field Custom Field..." type="hidden" /></code></pre>
        <p>
        If you require any existing Campaign Monitor subscriber\'s data to be merged, you can provide
        the fields you want to merge like so:
      </p>
        <pre class="XML"><code>&lt;input name="campaignmonitor[merge-fields]" value="Name of Custom Field1, Name of CustomField2" type="hidden" /></code></pre>';
		}

		public function load(){
			if(isset($_POST['action'][self::ROOTELEMENT])) {
				return $this->__trigger();
			}
		}

		protected function __trigger(){
			
			$push = true;
			
			$fields = '<fields>';
			
			//check "FIRST NAME"
			if(empty($_POST['firstname'])) {
				$push = false;
			}
			$fields .= sprintf('<%s type="%s">%s</%s>',
					'firstname',
					(empty($_POST['firstname']) ? 'missing' : 'valid'),
					(empty($_POST['firstname']) ? '' : $_POST['firstname']),
					'firstname');
			
			//check "LAST NAME"
			if(empty($_POST['lastname'])) {
				$push = false;
			}
			$fields .= sprintf('<%s type="%s">%s</%s>',
					'lastname',
					(empty($_POST['lastname']) ? 'missing' : 'valid'),
					(empty($_POST['lastname']) ? '' : $_POST['lastname']),
					'lastname');
					
			//check "EMAIL"
			if(empty($_POST['email'])) {
				$push = false;
			}
			$fields .= sprintf('<%s type="%s">%s</%s>',
					'email',
					(empty($_POST['email']) ? 'missing' : 'valid'),
					(empty($_POST['email']) ? '' : $_POST['email']),
					'email');
			
			//check "CASE TYPE"
			if(empty($_POST['casetype'])) {
				$push = false;
			}
			$fields .= sprintf('<%s type="%s">%s</%s>',
					'casetype',
					(empty($_POST['casetype']) ? 'missing' : 'valid'),
					(empty($_POST['casetype']) ? '' : $_POST['casetype']),
					'casetype');
			
			//set "NOTE"
			$fields .= sprintf('<%s type="%s">%s</%s>',
					'note',
					'valid',
					(empty($_POST['note']) ? '' : $_POST['note']),
					'note');
			
			$fields .= '</fields>';
			
			include(EXTENSIONS . '/highrise/lib/push2Highrise.php');
			
			$response = null;
			if( $push ) {
				$result = $this->doPush($_POST);
			}
			
			$condition = null;
			if(empty($result)) {
				$condition = 'error';
				$response = '<response />';
			} else {
				$condition = $result['result'];
				if($condition == 'ok') {
					$response = $result['response'];
				} else {
					$response = '<message>'.$result['response'].'</message>';
				}
			}
			
			$result = sprintf('<%s result="%s">%s%s</%s>',self::ROOTELEMENT,$condition,$fields,$response,self::ROOTELEMENT);
			return $result;
		}
		
		private function doPush($post) {
			$response = '';
			
			$url = 'https://1009design1.highrisehq.com';
			$token = 'd3fc3b65ae74373d9bc22a15ff66a796';

			$api = new Push_Highrise($url,$token);
			
			$id = $api->getContactId($post);
			if($id === false) {
				return array('result'=>'error','response'=>'can not connect to highrise.');
			}
			if($id=='-1') {
				$people = $api->pushContact($post);
				if($people === false) {
					return array('result'=>'error','response'=>'Failed to push contact to highrise.');
				}else if( !preg_match('/(\<\?xml[\d\D]*\?\>)/i', $people) ) {
					return array('result'=>'error','response'=>$people);
				}
				
				$response .= substr($people,strpos($people,'<response>'));
				$id = $api->getContactId($post);
			}
			
			$kases = $api->listCases();
			if($kases === false) {
				return array('result'=>'error','response'=>'Failed to push case to highrise.');
			} else if( !preg_match('/(\<\?xml[\d\D]*\?\>)/i', $kases) ) {
				return array('result'=>'error','response'=>$kases);
			}
			$kases = simplexml_load_string($kases);
			
			//if case not exists, create one
			$casename = 'Request from "'.$post['casetype'].'"';
			$caseId = false;
			foreach($kases as $kase) {
				if($kase->name == $casename) {
					$caseId = $kase->id;
				}
			}
			
			if( $caseId === false ) {
				//push a case
				$request = array(
					'casename' => $casename
				);
				$kase = $api->pushCase($request);
				
				if($kase === false) {
					return array('result'=>'error','response'=>'Failed to push case to highrise.');
				} else if( !preg_match('/(\<\?xml[\d\D]*\?\>)/i', $kase) ) {
					return array('result'=>'error','response'=>$kase);
				}
				
				$response .= substr($kase,strpos($kase,'<kase>'));
				$kase = simplexml_load_string($kase);
				
				$caseId = $kase->id;
			}
			
			
			//push a note
			$request = array(
				'id'=>$caseId,
				'type'=>'Kase',
				'note'=>'First Name: ' . $post['firstname'] . "\r\n"
						.'Last Name: ' . $post['lastname'] . "\r\n"
						.'Note: ' . $post['note']
			);
			$note = $api->pushNote($request);
			
			if($note === false) {
				return array('result'=>'error','response'=>'Failed to push note to highrise.');
			} else if( !preg_match('/(\<\?xml[\d\D]*\?\>)/i', $note) ) {
				return array('result'=>'error','response'=>$note);
			}
			
			$response .= substr($note,strpos($note,'<note>'));
			
			//everything is ok, return success
			return array('result'=>'ok','response'=>$response);
		}
	}
