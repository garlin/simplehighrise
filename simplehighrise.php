<?php

# SimpleHighrise: PHP wrapper class for Highrise API
# Version: vGamma
# Author: Garlin Gilchrist II
# Project page: http://www.activistnerd.com/simplehighrise-a-php-wrapper-class-for-the-highrise-api/

class SimpleHighrise {
	var $username = "";
	var $token = "";
	var $result_type = "";
		
	function SimpleHighrise($u, $t, $result = "raw") {
		$this->username = $u;
		$this->token = $t;
		$this->result_type = $result;
	}
  
  function create_request($parameters) {
		$request_payload = "";
		if ((!empty($parameters)) && (is_array($parameters))) {
			foreach($parameters as $key => $value) {
				if (!is_array($value)) {
					$request_payload .= ("<".$key.">".$value."</".$key.">\r\n");
				} else {
					$request_payload .= "<".$key.">\r\n";
					$request_payload .= $this->create_request($value);
					$request_payload .= "</".$key.">\r\n";
				}
			}
		}
		return $request_payload;
	}
	
	function curl_request($url, $verb = "", $request_body) {
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
    if (!empty($verb)) {
      if (!empty($request_body)) {        
        if ($verb == "PUT") {          
          // TODO: Add PUT Logic for Update Ops
        }
        elseif ($verb == "POST") {
          curl_setopt ($ch, CURLOPT_HTTPHEADER, Array("Content-Type:application/xml"));
          curl_setopt($ch, CURLOPT_POSTFIELDS, $request_body);
        }
      }
      else {
        if ($verb == "DELETE") {
          curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
        }
        else {
          // Simple GET
        }
      }
    }
    else {      
    }
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_USERPWD, $this->token);
		$data = curl_exec($ch);
		curl_close($ch);
		return $data;
	}
  
  function convertXmlObjToArr($obj, &$arr) {
    $children = $obj->children();
    foreach ($children as $elementName => $node) {
      $nextIdx = count($arr);
      $arr[$nextIdx] = array();
      $arr[$nextIdx]['@name'] = strtolower((string)$elementName);
      $arr[$nextIdx]['@attributes'] = array();
      $attributes = $node->attributes();
      foreach ($attributes as $attributeName => $attributeValue) {
        $attribName = strtolower(trim((string)$attributeName));
        $attribVal = trim((string)$attributeValue);
        $arr[$nextIdx]['@attributes'][$attribName] = $attribVal;
      }
      $text = (string)$node;
      $text = trim($text);
      if (strlen($text) > 0) {
        $arr[$nextIdx]['@text'] = $text;
      }
      $arr[$nextIdx]['@children'] = array();
      $this->convertXmlObjToArr($node, $arr[$nextIdx]['@children']);
    }
    return;
  }
  
  function request($path, $parameters = "", $verb = "") {
		$url = "http://".$this->username.".highrisehq.com/".$path;
		if (!empty($parameters)) {
      $request_body = $this->create_request($parameters)."";
    }
    if (empty($verb)) {
      $verb = "GET";
    }
		$result = $this->curl_request($url, $verb, $request_body);
    
    if ($result[0] != '<') {
      return -1;
    }
        
		if ($this->result_type == "simplexml") {
			$result = simplexml_load_string($result);
		}
    elseif ($this->result_type == "array") {
      $xml = simplexml_load_string($result);
      $temp = $this->convertXmlObjToArr($xml, $array);
      $result = $array;
    }
    
		return($result);
	}
  
  # people
  
  function show_person($id) {
    return($this->request("people/".$id.".xml"));
  }

  function list_people($offset = 0) {
		return($this->request("people.xml?n=".$offset.""));
	}
  
  function list_people_by_title($title) {
    $encodedTitle = urlencode($title);
		return($this->request("people.xml?title=".$encodedTitle.""));
	}
  
  function list_people_by_tag($tagId) {
		return($this->request("people.xml?tag_id=".$tagId.""));
	}
  
  function list_people_by_company($companyId) {
		return($this->request("/companies/".$companyId."/people.xml"));
	}
  
  function search_people($term) {
    $encodedTerm = urlencode($term);
		return($this->request("people/search.xml?term=".$encodedTerm.""));
	}
  
  // date format: yyyymmddhhmmss
  function list_people_since_date($date, $offset = 0) {    
		return($this->request("people.xml?since=".$date."&n=".$offset.""));
	}
  
  function create_new_person($firstName = "", $lastName = "", $phoneNumber = "", $companyName = "Just Added", $emailAddress = "foo@bar.com", $location = "Work") {
    return($this->request("people.xml", array("person" => array("first-name" => $firstName, "last-name" => $lastName, "company-name" => $companyName, "contact-data" => array("email-addresses" => array("email-address" => array("address" => $emailAddress, "location" => $location)), "phone-numbers" => array("phone-number" => array("number" => $phoneNumber, "location" => $location))))), "POST"));
  }
    
  function update_person() {    
  }
  
  function delete_person($id) {
    return($this->request("people/".$id.".xml", "", "DELETE"));
  }
  
  function get_person_id_from_name($name) {    
    $result = $this->search_people($name);
    if ($this->result_type == "simplexml") {
      $xml = new SimpleXMLElement($result);
      return($xml->person->id);
    }
    elseif ($this->result_type == "array") {
      $id = $result[0]['@children'][6]['@text'];
      return ($id);
    }    
  }
  
  # companies
  
  function show_company($id) {
    return($this->request("companies/".$id.".xml"));
  }

  function list_companies($offset = 0) {
		return($this->request("companies.xml?n=".$offset.""));
	}
  
  function list_companies_by_tag($tagId) {
		return($this->request("companies.xml?tag_id=".$tagId.""));
	}
  
  function search_companies($term) {
    $encodedTerm = urlencode($term);
		return($this->request("companies/search.xml?term=".$encodedTerm.""));
	}
  
  // date format: yyyymmddhhmmss
  function list_companies_since_date($date, $offset = 0) {    
		return($this->request("companies.xml?since=".$date."&n=".$offset.""));
	}
  
  function create_new_company($companyName = "", $phoneNumber = "123-456-7890", $emailAddress = "foo@bar.com", $location = "Work") {
    return($this->request("companies.xml", array("company" => array("name" => $companyName, "contact-data" => array("email-addresses" => array("email-address" => array("address" => $emailAddress, "location" => $location)), "phone-numbers" => array("phone-number" => array("number" => $phoneNumber, "location" => $location))))), "POST"));
  }
  
  function update_company($id) {
  }
  
  function delete_company($id) {
    return($this->request("companies/".$id.".xml", "", "DELETE"));
  }
  
  function get_company_id_from_name($name) {
    $result = $this->search_companies($name);
    if ($this->result_type == "simplexml") {
      $xml = new SimpleXMLElement($result);
      return($xml->company->id);
    }
    elseif ($this->result_type == "array") {
      $id = $result[0]['@children'][6]['@text'];
      return ($id);
    }
  }
  
  # cases
  
  function show_case($id) {
    return($this->request("kases/".$id.".xml"));
  }
  
  function list_open_cases() {
		return($this->request("kases/open.xml"));
	}
  
  function list_closed_cases() {
		return($this->request("kases/closed.xml"));
	}
  
  function create_new_case($name) {
    return($this->request("kases.xml", array("kase" => array("name" => $name)), "POST"));
  }
  
  function close_case($id, $closureDate) {
  }
  
  function delete_case($id) {
    return($this->request("kases/".$id.".xml", "", "DELETE"));
  }
  
  # notes
  
  function show_note($id) {
    return($this->request("notes/".$id.".xml"));
  }
  
  function list_notes_by_person($personId) {
		return($this->request("people/".$personId."/notes.xml"));
	}
  
  function list_notes_by_company($companyId) {
		return($this->request("companies/".$companyId."/notes.xml"));
	}
  
  function list_notes_by_case($caseId) {
		return($this->request("kases/".$caseId."/notes.xml"));
	}
  
  function create_new_note_person($personId, $body = "", $subjectType = "Party") {
    return($this->request("people/".$personId."/notes.xml", array("note" => array("body" => $body, "subject-type" => $subjectType)), "POST"));
  }
  
  function create_new_note_company($companyId, $body = "", $subjectType = "Party") {
    return($this->request("companies/".$companyId."/notes.xml", array("note" => array("body" => $body, "subject-type" => $subjectType)), "POST"));
  }
  
  function create_new_note_case($caseId, $body = "", $subjectType = "Kase") {
    return($this->request("kases/".$caseId."/notes.xml", array("note" => array("body" => $body, "subject-type" => $subjectType)), "POST"));
  }
  
  function update_note($id) {
  }
  
  function delete_note($id) {
    return($this->request("notes/".$id.".xml", "", "DELETE"));
  }
  
  # emails
  
  function show_email($id) {
    return($this->request("emails/".$id.".xml"));
  }
  
  function list_emails_by_person($personId) {
		return($this->request("people/".$personId."/emails.xml"));
	}
  
  function list_emails_by_company($companyId) {
		return($this->request("companies/".$companyId."/emails.xml"));
	}
  
  function list_emails_by_case($caseId) {
		return($this->request("kases/".$caseId."/emails.xml"));
	}
  
  function create_new_email($title, $body, $subjectId, $subjectType = "Party") {
    return($this->request("emails.xml", array("email" => array("title" => $title, "body" => $body, "subject-id" => $subjectId, "subject-type" => $subjectType)), "POST"));
  }
  
  function create_new_email_person($personId, $title, $body) {
    return($this->request("people/".$personId."/emails.xml", array("email" => array("title" => $title, "body" => $body, "subject-id" => $personId, "subject-type" => "Party")), "POST"));
  }
  
  function create_new_email_company($companyId, $title, $body) {
    return($this->request("companies/".$companyId."/emails.xml", array("email" => array("title" => $title, "body" => $body, "subject-id" => $companyId, "subject-type" => "Party")), "POST"));
  }
  
  function update_email($id) {
  }
  
  function delete_email($id) {
    return($this->request("emails/".$id.".xml", "", "DELETE"));
  }
  
  # tasks
  
  function show_task($id) {
    return($this->request("tasks/".$id.".xml"));
  }
  
  function list_upcoming_tasks() {
		return($this->request("tasks/upcoming.xml"));
	}
  
  function list_upcoming_tasks_person($personId) {
		return($this->request("people/".$personId."/tasks.xml"));
	}
  
  function list_upcoming_tasks_company($companyId) {
		return($this->request("companies/".$companyId."/tasks.xml"));
	}
  
  function list_upcoming_tasks_case($caseId) {
		return($this->request("kases/".$caseId."/tasks.xml"));
	}
  
  function list_assigned_tasks() {
		return($this->request("tasks/assigned.xml"));
	}
  
  function list_completed_tasks() {
		return($this->request("tasks/completed.xml"));
	}
  
  function create_new_general_task($body, $frame = "later") {
    return($this->request("tasks.xml", array("task" => array("body" => $body, "frame" => $frame)), "POST"));
  }
  
  // time format: 2007-03-10T15:11:52Z
  function create_new_specific_time_task($body, $time = "2007-03-10T15:11:52Z", $category = 1) {
    return($this->request("tasks.xml", array("task" => array("body" => $body, "alert-at" => $time, "category-id" => $category)), "POST"));
  }
  
  // time format: 2007-03-10T15:11:52Z
  function complete_task($id, $time = "2007-03-10T15:11:52Z") {
    return($this->request("tasks/".$id."/complete.xml", array("task" => array("done-at" => $time)), "POST"));
  }
  
  function update_task($id) {
  }
  
  function delete_task($id) {
    return($this->request("tasks/".$id.".xml", "", "DELETE"));
  }
  
  # comments
  
  function show_comment($id) {
    return($this->request("comments/".$id.".xml"));
  }
  
  function list_comments_from_note($noteId) {
		return($this->request("notes/".$noteId."/comments.xml"));
	}
  
  function list_comments_from_email($emailId) {
		return($this->request("emails/".$emailId."/comments.xml"));
	}
  
  function create_new_comment($body, $parentId) {
    return($this->request("comments.xml", array("comment" => array("body" => $body, "parent-id" => $parentId)), "POST"));
  }
  
  function update_comment($id) {
  }
  
  function delete_comment($id) {
    return($this->request("comments/".$id.".xml", "", "DELETE"));
  }
  
  # users
  
  // function takes USER id, not person id
  function show_user($id) {
    return($this->request("users/".$id.".xml"));
  }
  
  function list_users() {
		return($this->request("users.xml"));
	}
  
  # groups
  
  function show_group($id) {
    return($this->request("groups/".$id.".xml"));
  }
  
  function list_groups() {
		return($this->request("groups.xml"));
	}
  
  function create_new_group($name) {
    return($this->request("groups.xml", array("group" => array("name" => $name)), "POST"));
  }
  
  function update_group($id) {
  }
  
  function delete_group($id) {
    return($this->request("groups/".$id.".xml", "", "DELETE"));
  }
  
  # memberships
  
  function show_membership($id) {
    return($this->request("memberships/".$id.".xml"));
  }
  
  function list_memberships() {
		return($this->request("memberships.xml"));
	}
  
  function create_new_membership($userId, $groupId) {
    return($this->request("memberships.xml", array("memberships" => array("membership" => array ("user" => $userId, "group" => $groupId))), "POST"));
  }
  
  function delete_membership($id) {
    return($this->request("membership/".$id.".xml", "", "DELETE"));
  }
}
?>
