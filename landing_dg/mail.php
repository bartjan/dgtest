<?php

error_reporting(E_WARNING);
/*
	OXYLUS Development web framework
	copyright (c) 2002-2008 OXYLUS Development
		web:  www.oxylus-development.com
		mail: support@oxylus-development.com

	$Id: name.php,v 0.0.1 dd/mm/yyyy hh:mm:ss oxylus Exp $
	description
*/

print_r($_POST);

$conf = array(

	"mail"		=> array(
	
		"method"		=> "php-default",			// swift-php , swift-sendmail , swift-smtp , php-default
		"server"		=> "",
		"port"			=> "",
		"encription"	=> "",					//	plain , ssl , tsl 

		"auth"			=> false,				//true , false
		"username"		=> "",
		"password"		=> "",

		"sendmail"		=> "/usr/sbin/sendmail"	//default sendmail path
	),
	
	"notification_email" => array(
		"enable"		=> true,

		"to"			=> "bartjan@dg-internetbureau.nl",
		"to_name"		=> "Bart Jan van Engelen",

		"from"			=> "no-reply@dg-internetbureau.nl",
		"from_name"		=> "DG Landings Formulier",

		"subject"		=> "Nieuw bericht via landings form: {SUBJECT}",
		"type"			=> "html",
		"message"		=> <<<EOD
	<p>
	Er is een nieuwe contactaanvraag op een landingspagina binnengekomen:
<br /><br />	
		Naam: {NAME}<br>
		Email: {EMAIL}<br>
		Onderwerp: {SUBJECT}<br>
		Bericht: {MESSAGE}
	</p>
EOD
		),


	"autoresponder_email" => array(
		"enable"		=> true,

		"from"			=> "no-reply@dg-internetbureau.nl",
		"from_name"		=> "DG Internetbureau",

		"subject"		=> "Bedankt voor uw interesse",
		"type"			=> "html",
		"message"		=> <<<EOD
	<p>
	Bedankt voor uw interesse in DG Internetbureau. Dit zijn de gegevens die we van u ontvangen hebben:<br /><br />
	
		Naam: {NAME}<br>
		Email: {EMAIL}<br>
		Onderwerp: {SUBJECT}<br>
		Bericht: {MESSAGE}
<br /><br />		
	We zullen zo spoedig mogelijk contact met u opnemen.
<br /><br />	
	Met vriendelijke groet,
<br /><br />	
	- DG Internetbureau
	</p>
EOD
		),


	"fields"	=> array(		
		"name"	=> array("required"	=> true,),
		"email"	=> array("required"	=> true,),
		"subject"	=> array("required"	=> true,),
		"message"	=> array("required"	=> true,),
	),

);


## starting the actual code, you have nothing else to configure from this point forward.

if (stristr($conf["mail"]["method"] , "swift-")) {
	require_once 'swift/swift_required.php'; 
}


	function SendMail() {
		global $conf;

		$params = AStripSlasshes(func_get_args());	
		//check to see the numbers of the arguments

		switch (func_num_args()) {
			case 1:
				$email = $params[0];
				$vars = array();
			break;

			case 2:
				$email = $params[0];
				$vars = $params[1];
			break;

			case 3:
				$to = $params[0];
				$email = $params[1];
				$vars = $params[2];
			break;

			case 4:
				$to = $params[0];
				$to_name = $params[1];
				$email = $params[2];
				$vars = $params[3];
			break;
		}
		
		if ($email["email_status"] == 1) {
			return true;
		}		
		
		$msg = new CTemplate(stripslashes($email["email_body"]) , "string");
		$msg = $msg->Replace($vars);

		$sub = new CTemplate(stripslashes($email["email_subject"]) , "string");
		$sub = $sub->Replace($vars);

		$email["email_from"] = new CTemplate(stripslashes($email["email_from"]) , "string");
		$email["email_from"] = $email["email_from"]->Replace($vars);

		$email["email_from_name"] = new CTemplate(stripslashes($email["email_from_name"]) , "string");
		$email["email_from_name"] = $email["email_from_name"]->Replace($vars);

		if (!$email["email_reply"]) 
			$email["email_reply"] = $email["email_from"];
		if (!$email["email_reply_name"]) 
			$email["email_reply_name"] = $email["email_from_name"];
		

		if ($conf["mail"]["method"] == "php-default") {
	
			//prepare the headers
			$headers  = "MIME-Version: 1.0\r\n";
			$headers .= "Content-type: text/html\r\n";

			//prepare the from fields
			if (!$email["email_hide_from"]) {
				$headers .= "From: {$email[email_from_name]}<{$email[email_from]}>\r\n";
				$headers .=	"Reply-To: {$email[email_reply_name]}<{$email[email_reply]}>\r\n";
			}

			$headers .= $email["headers"];
			
			if (!$email["email_hide_to"]) {
				return @mail($email["email_to"] , $sub, $msg,$headers);		
			} else {
			}

			$headers .=	"X-Mailer: PHP/" . phpversion();

			return mail($to, $sub, $msg,$headers);				

		} else {

			$recipients = array($email["email_to"] => $email["email_to_name"] ? $email["email_to_name"] : $email["email_to"]);	
			$from = array($email["email_from"] => $email["email_from_name"] ? $email["email_from_name"] : $email["email_from"]);	
			$body = $msg;
			$subject = $sub;
		
			//initialize the mail sending
			switch ($conf["mail"]["method"]) {
				case "swift-smtp":
					$transport = Swift_SmtpTransport::newInstance(
						$conf["mail"]["server"],
						$conf["mail"]["port"]
					);

					if ($conf["mail"]["auth"]) {
						$transport->setUsername($conf["mail"]["username"]);
						$transport->setPassword($conf["mail"]["password"]);
					}
					
					switch ($conf["mail"]["encription"]) {
						case "ssl":
							$transport->setEncryption("ssl");
						break;

						case "tls":
							$transport->setEncryption("tls");
						break;
					}
				break;

				case "swift-sendmail":
					$transport = Swift_SmtpTransport::newInstance(
						$conf["mail"]["sendmail"]
					);
				break;
		
				case "swift-php":
					$transport = Swift_SmtpTransport::newInstance();
				break;
			}
			
			
			$message = Swift_Message::newInstance($subject);
			$message->setBody(
				$body , 
				"text/html"
			);

			$message->setTo($recipients);

			$message->setFrom($from);

			$mailer = Swift_Mailer::newInstance($transport);
			$result = $mailer->send($message);

		}
	} 




	function AStripSlasshes($array) {
		if (is_array($array))		
			foreach ($array as $key => $item)
				if (is_array($item)) 
					$array[$key] = AStripSlasshes($item);
				else		
					$array[$key] = stripslashes($item);
		else
			return stripslashes($array);
		
		return $array;
	}

$_TSM = array();


class CTemplate {
	/**
	* template source data
	*
	* @var string
	*
	* @access private
	*/
	var $input;

	/**
	* template result data
	*
	* @var string
	*
	* @access public
	*/
	var $output;

	/**
	* template blocks if any
	*
	* @var array
	*
	* @access public
	*/
	var $blocks;

	/**
	* constructor which autoloads the template data
	*
	* @param string $source			source identifier; can be a filename or a string var name etc
	* @param string $source_type	source type identifier; currently file and string supported
	*
	* @return void
	*
	* @acces public
	*/
	function CTemplate($source,$source_type = "file") {
		$this->Load($source,$source_type);
	}

	/**
	* load a template from file. places the file content into input and output
	* also setup the blocks array if any found
	*
	* @param string $source			source identifier; can be a filename or a string var name etc
	* @param string $source_type	source type identifier; currently file and string supported
	*
	* @return void
	*
	* @acces public
	*/
	function Load($source,$source_type = "file") {
		switch ($source_type) {
			case "file":
				$this->template_file = $source;
				// get the data from the file
				$data = GetFileContents($source);
				//$data = str_Replace('$','\$',$data);
			break;
			
			case "rsl":
			case "string":
				$data = $source;
			break;
		}


		// blocks are in the form of <!--S:BlockName-->data<!--E:BlockName-->
		preg_match_all("'<!--S\:.*?-->.*?<!--E\:.*?-->'si",$data,$matches);

		// any blocks found?
		if (count($matches[0]) != 0)
			// iterate thru `em
			foreach ($matches[0] as $block) {
				// extract block name
				$name = substr($block,strpos($block,"S:") + 2,strpos($block,"-->") - 6);

				// cleanup block delimiters
				$block = substr($block,9 + strlen($name),strlen($block) - 18 - strlen($name) * 2);

				// insert into blocks array
				$this->blocks["$name"] = new CTemplate($block,"string");
			}

		// cleanup block delimiters and set the input/output
		$this->input = $this->output = preg_replace(array("'<!--S\:.*?-->(\r\n|\n|\n\r)'si","'<!--E\:.*?-->(\r\n|\n|\n\r)'si"),"",$data);
	}

	/**
	* replace template variables w/ actual values
	*
	* @param array $vars	array of vars to be replaced in the form of "VAR" => "val"
	* @param bool $clear	reset vars after replacement? defaults to TRUE
	*
	* @return string the template output
	*
	* @acces public
	*/
	function Replace($vars,$clear = TRUE) {
		if (is_array($vars)) {
			foreach ($vars as $key => $var) {
				if (is_array($var)) {
					unset($vars[$key]);
				}				
			}			
		}
		
		// init some temp vars
		$patterns = array();
		$replacements = array();

		// build patterns and replacements
		if (is_array($vars))
			// just a small check		
			foreach ($vars as $key => $val) {
				$patterns[] = "/\{" . strtoupper($key) . "\}/";

				//the $ bug
				$replacements[] = str_replace('$','\$',$val);
			}

		// do regex		
		$result = $this->output = @preg_replace($patterns,$replacements,$this->input);

		// do we clear?
		if ($clear == TRUE)
			$this->Clear();

		// return output
		return $result;
	}

	function SepReplace($ssep , $esep , $vars,$clear = TRUE) {
		if (is_array($vars)) {
			foreach ($vars as $key => $var) {
				if (is_array($var)) {
					unset($vars[$key]);
				}				
			}			
		}
		
		// init some temp vars
		$patterns = array();
		$replacements = array();

		// build patterns and replacements
		if (is_array($vars))
			// just a small check		
			foreach ($vars as $key => $val) {
				$patterns[] = $ssep . strtoupper($key) . $esep;

				//the $ bug
				$replacements[] = str_replace('$','\$',$val);
			}

		// do regex		
		$result = $this->output = @preg_replace($patterns,$replacements,$this->input);

		// do we clear?
		if ($clear == TRUE)
			$this->Clear();

		// return output
		return $result;
	}

	/**
	* replace a single template variable
	*
	* @param string $var	variable to be replaced
	* @param string $value	replacement
	* @param bool $perm		makes the change permanent [i.e. replaces input also]; defaults to FALSE
	*
	* @return string result of replacement
	*
	* @acces public
	*/
	function ReplaceSingle($var,$value,$perm = FALSE) {

		if ($perm)
			$this->input = $this->Replace(array("$var" => $value));
		else		
			return $this->Replace(array("$var" => $value));
	}

	/**
	* resets all the replaced vars to their previous status
	*
	* @return void
	*
	* @acces public
	*/
	function Clear() {
		$this->output = $this->input;
	}

	/**
	* voids every template variable
	*
	* @return void
	*
	* @acces public
	*/
	function EmptyVars() {
		global $_TSM;

		//$this->output = $this->ReplacE($_TSM["_PERM"]);
		//return$this->output = preg_replace("'{[A-Z]}'si","",$this->output);
		return $this->output = preg_replace("'{[A-Z_\-0-9]*?}'si","",$this->output);
		//return $this->output = preg_replace("'{[\/\!]*?[^{}]*?}'si","",$this->output);
	}

	/**
	* checks if the specified template block exists
	*
	* @param string	$block_name	block name to look for
	*
	* @return bool TRUE if exists or FALSE if it doesnt
	*
	* @access public
	*/
	function BlockExists($block_name) {
		return isset($this->blocks[$block_name]) && is_object($this->blocks[$block_name])? TRUE : FALSE;

	}

/*
	function Block($block,$vars = array(),$return_error = false) {
		if ($this->BlockExists($block))
			return $this->blocks[$block]->Replace($vars);
		else {
			return "";
		}

				
	}
*/

	/*Extra functions to keep the compatibility with the new CTemplateDynamic library*/

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function BlockReplace($block , $vars = array(), $clear = true){
		if (!is_object($this->blocks[$block]))
			echo "CTemplate::{$this->template_file}::$block Doesnt exists.<br>";
		
		return $this->blocks[$block]->Replace($vars , $clear);
	}

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function BlockEmptyVars($block , $vars = array(), $clear = true) {
		if (!is_object($this->blocks[$block]))
			echo "CTemplate::{$this->template_file}::$block Doesnt exists.<br>";

		if (is_array($vars) && count($vars))
			$this->blocks[$block]->Replace($vars , false);
		
		return $this->blocks[$block]->EmptyVars();
	}
	
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function Block($block) {
		if (!is_object($this->blocks[$block]))
			echo "CTemplate::{$this->template_file}::$block Doesnt exists.<br>";

		return $this->blocks[$block]->output;
	}
	
	
}


/**
* description
*
* @library	
* @author	
* @since	
*/
class CTemplateStatic{
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	
	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function Replace($tmp , $data = array()) {
		$template = new CTemplate($tmp , "string");
		return $template->replace($data);
	}

	function EmptyVars($tmp , $data = array()) {
		$template = new CTemplate($tmp , "string");

		if (count($data)) {
			$template->replace($data , false);
		}
		
		return $template->emptyvars();
	}

	/**
	* description
	*
	* @param
	*
	* @return
	*
	* @access
	*/
	function ReplaceSingle($tmp , $var , $value) {
		return CTemplateStatic::Replace(
			$tmp , 
			array(
				$var => $value
			)
		);
	}
	
	
}


foreach ($conf["fields"] as $key => $val) {

	if ($val["required"]) {
		if (!$_POST[$key]) {
			echo "0";
			die();
		} 
	}

	$_POST[$key] = stripslashes($_POST[$key]);
	
}


//check if the notification should be sent
if ($conf["notification_email"]["enable"] == true) {

	$vars = $_POST;

	foreach ($conf["notification_email"] as $key => $val) {
		$conf["notification_email"][$key] = CTemplateStatic::Replace($val , $vars);
	}

	//process the notify email
	$email = array(
		"email_to"			=> $conf["notification_email"]["to"],
		"email_to_name"		=> $conf["notification_email"]["to_name"],

		"email_from"		=> $_POST["email"],
		"email_from_name"	=> $_POST["name"],

		"email_subject"		=> $conf["notification_email"]["subject"],
		"email_body"		=> $conf["notification_email"]["message"],
		"email_type"		=> $conf["notification_email"]["type"]
	);

	foreach ($email as $key => $val) {
		$email[$key] = CTemplateStatic::Replace($val , $vars);
	}

	SendMail($email);
}

//check if the notification should be sent
if ($conf["autoresponder_email"]["enable"] == true) {
	$vars = $_POST;

	foreach ($conf["autoresponder_email"] as $key => $val) {
		$conf["autoresponder_email"][$key] = CTemplateStatic::Replace($val , $vars);
	}

	//process the notify email
	$email = array(
		"email_to"			=> $_POST["email"],
		"email_to_name"		=> $_POST["name"],

		"email_from"		=> $conf["autoresponder_email"]["from"],
		"email_from_name"	=> $conf["autoresponder_email"]["from_name"],

		"email_subject"		=> $conf["autoresponder_email"]["subject"],
		"email_body"		=> $conf["autoresponder_email"]["message"],
		"email_type"		=> $conf["autoresponder_email"]["type"]
	);

	foreach ($email as $key => $val) {
		$email[$key] = CTemplateStatic::Replace($val , $vars);
	}

	SendMail($email);
}

echo "1";
die();

?>