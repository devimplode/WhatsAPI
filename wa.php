<?php
	class wa{
		private $state=false;//status of this client
		private $last_contact=false;
		private $contactlist=false;
		private $lastTimestamp=false;//last timestamp we postet to screen output
		private $version="v0.1";
		private $status=true;//status (true|false) of logged in client
		private $idlesince=false;
		private $autoafk=60;//time to wait in seconds before status automaticly switches to unavailable
		private $autoStatus=false;
		private $debug=false;
		private $wp=false;
		private $tmp=array();

		public function init(){
			require_once("src/php/whatsprot.class.php");
			require_once("src/php/contactlist.php");
			require_once("src/php/lib_crypt.php");
			$this->contactlist=new contactlist();
			$this->users=new contactlist("users");
			$this->work();
		}
		protected function work(){
			while(true){
				switch($this->state){
					case false:
						//startup
						px("Whatsapp for comandline ".$this->version);
						pnl();
						p("Do you have an account alredy?");
						p("Choose wisely!");
						p("----------------------",">");
						p("Login with account","1");
						p("Create a new account","2");
						p("Login with whatsapp credentials (no data would be saved at this service)","3");
						p("----------------------",">");
						p("Exit","q");
						p("----------------------",">");
						$this->state="start_i";
						break;
					case 'start_i':
						$input=$this->getInput();
						if($input!=''){
							switch($input){
								case '1':
									$this->state="login_user";
								break;
								case '2':
									$this->state="create_user";
								break;
								case '3':
									$this->state="login_wa";
								break;
								default:
									$this->state="exit";
								break;
							}
						}
						unset($input);
						break;
					case 'create_user':
						pnl();
						p("For the creation of a new account you need your Whatsapp login credentials.");
						p("First type your mobile number of your Whatsapp account");
						pnl();
						p("Mobile number: ",">");
						$this->state="create_user_isender";
						break;
					case 'create_user_isender':
						$sender=$this->getInput();
						if($sender!=''){
							$this->sender=$sender;
							pnl();
							p("Now you have to type in the 'password'.");
							p("If you have an Android you must input the IMEI of your mobile.");
							p("If you have an iPhone, please input the MAC of your device.");
							pnl();
							p("Password:",">");
							$this->state="create_user_ipass";
						}
						unset($sender);
						break;
					case 'create_user_ipass':
						$pass=$this->getInput();
						if($pass!=''){
							pnl();
							$this->imei=$pass;
							if(stripos($pass,":")!==false){
								$this->pd("MAC Address detected. Using iOS authentification.");
								$pass = strtoupper($pass);
								$pass = md5($pass.$pass);
							}
							else{
								$this->pd("MAC Address not detected. Using default authentification.");
								$pass = md5(strrev($pass));
							}
							p("Validating login credentials...");
							p("requesting r.whatsapp.net ...");
							if(stristr(@file_get_contents("https://r.whatsapp.net/v1/exist.php?cc=".substr($this->sender,0,2)."&in=".substr($this->sender,2)."&udid=".$pass),'status="ok"') === false){
								px("Requesting fail'd. Please check your mobile number and password");
								pnl();
								$this->state="create_user";
							}
							else{
								p("Validation successful!");
								p("Now we can create you an account.");
								p("Please choose a alias or nick name. This is not your account name!");
								pnl();
								p("Nickname:",">");
								$this->state="create_user_inickname";
							}
						}
						unset($pass);
						break;
					case 'create_user_inickname':
						$nick=$this->getInput();
						if($nick!=''){
							pnl();
							$this->nickname=$nick;
							p("oh god I'm soooooo sorry :( I forgot this all the time...");
							p("Dear ".$this->nickname.", welcome to Whatsapp!");
							p("Now we know us, you should get your login credentials.");
							p("Please choose a login name.");
							pnl();
							p("Login Name:",">");
							$this->state="create_user_iusername";
						}
						unset($nick);
						break;
					case 'create_user_iusername':
						$user=$this->getInput();
						if($user!=''){
							pnl();
							$this->username=$user;
							if($this->users->getByName($user)!=false){
								px("Sorry, someone was faster then you...");
								p("Please choose a other one.");
								pnl();
								p("Login Name:",">");
								unset($user);
								break;
							}
							p("Ok, that one is fine. Now a password");
							pnl();
							p("Password:",">");
							$this->state="create_user_ipassword";
						}
						unset($user);
						break;
					case 'create_user_ipassword':
						$pass=$this->getInput();
						if($pass!=''){
							pnl();
							$this->password=$pass;
							p("And again... (just to prevent typos)");
							pnl();
							p("Password:",">");
							$this->tmp['pwditime']=time();
							$this->state="create_user_ipasswordcheck";
						}
						unset($pass);
						break;
					case 'create_user_ipasswordcheck':
						$pass=$this->getInput();
						if($pass!=''){
							pnl();
							if($pass==$this->password){
								p("Ok ".$this->nickname.", we got everything...");
								$this->state="create_user_createuser_createaccount";
							}
							else{
								p("THIS IS NOT WHAT YOU TOLD ME IN THE FIRST TIME!!!");
								p("YOU DON'T DESERVE TO CHAT IF YOU AREN'T ABLE TO MIND YOUR F*CKING PASSWORDS FOR ".(time() - intval($this->tmp['pwditime']))." F*CKING SECONDS!!!");
								p("GTFO!");
								$this->pd("Wait what? You are running this in debug mode and couldn't keep your passwords? This.. is realy dumb.... But hey, you found the debug flag.... congratulation! You should be proud of you!");
								$this->pd("Seriously, this is dumb....");
								$this->pd("and know die in peace");
								//ToDo: Blacklist entry for this client
								die();
							}
						}
						unset($pass,$this->tmp['pwditime']);
						break;
					case 'create_user_createuser_createaccount':
						p("Let us create an account for you! ;)");
						if($this->users->getByName($this->username)!=false){
							//trouble
							px("umn... sry ".$this->nickname.", but it seems the username is already taken... please choose a other one");
							pnl();
							p("Login Name:",">");
							$this->state="create_user_createuser_iusername";
							break;
						}
						$this->user=array('sender'=>$this->sender,'imei'=>$this->imei,'nickname'=>$this->nickname);
						$this->users->createEntry($this->username,$this->sender,$this->username,false,array('wa_credentials'=>(crypt::encrypt(serialize($this->user),$this->password))));
						//$this->users->updateEntry($this->username,array('wa_credentials'=>(crypt::encrypt(serialize($this->user),$this->password))));
						pnl();
						px("Congratulation ".$this->nickname.", you have now an account! Please keep your login credentials in mind couse we cant reset your password, couse with this password we encrypted your whatsapp login data.");
						pnl();
						$this->state='connect';
						break;
					case 'create_user_createuser_iusername':
						$user=$this->getInput();
						if($user!=''){
							pnl();
							$this->username=$user;
							if($this->users->getByName($user)!=false){
								px("Sorry, even with this one was someone faster then you...");
								p("Please choose a other one.");
								pnl();
								p("Login Name:",">");
								unset($user);
								break;
							}
							p("Ok that one is fine :)");
							pnl();
							$this->state="create_user_createuser_createaccount";
						}
						unset($user);
						break;
					case 'login_user':
						//serverside safed credentials
						//username / password login
						pnl();
						p("User:",">");
						$this->state="login_user_iuser";
						break;
					case 'login_user_iuser':
						$user=$this->getInput();
						if($user!=''){
							pnl();
							$this->username=$user;
							p("Password:",">");
							$this->state="login_user_ipass";
						}
						unset($user);
						break;
					case 'login_user_ipass':
						$pass=$this->getInput();
						if($pass!=''){
							pnl();
							$this->password=$pass;
							$this->state="login_user_validating";
						}
						unset($pass);
						break;
					case 'login_user_validating':
						if($this->users->getByName($this->username)!=false){
							$this->user=@unserialize(crypt::decrypt($this->users->getByName($this->username)->get('wa_credentials'),$this->password));
							unset($this->username,$this->password);
							if(is_array($this->user)){
								$this->state="login_user_connect";
								break;
							}
						}
						px("Login failed! Please try again.");
						$this->state="login_user";
						break;
					case 'login_user_connect':
						$this->state="connect";
						break;
					case 'connect':
						if(!is_array($this->user)){
							$this->state="fail_login";
							break;
						}
						p("Logging in as '".$this->user['nickname']."' (".$this->user['sender'].")\n");
						$this->wp=new WhatsProt($this->user['sender'],$this->user['imei'],$this->user['nickname'],$this->debug);
						px("Check login credentials...");
						if(stristr(@file_get_contents("https://r.whatsapp.net/v1/exist.php?cc=".substr($this->user['sender'],0,2)."&in=".substr($this->user['sender'],2)."&udid=".$this->wp->encryptPassword()),'status="ok"') === false){
							px("Couldn't login! Maybe login credentials changed?");
							$this->state="fail_login";
						}
						//unset($url);
						px("Connecting to server...");
						$this->wp->Connect();
						px("Login...");
						$this->wp->Login();
						px("Connected!");
						$this->state="idle";
						break;
					case 'idle':
						//the default state - waiting for input and messages
						$this->pullMessages();
						$this->pullInput();
						$this->autoupdateStatus();
						break;
					case 'exit':
						//logout
						px("Good by and have a nice day!");
						exit();
						break;
					default:
						pnl();
						px("Something went wrong. Please try again.");
						exit(1);
				}
			}
		}
		//
		protected function pullMessages(){
			$this->wp->PollMessages();
			$buff = $this->wp->GetMessages();
			if(!empty($buff)){
				if($this->debug==true)
					print_r($buff);
				//parse buff
				$this->parseMessages($buff);
			}
		}
		protected function parseMessages($buff){
			if(is_array($buff) && count($buff)>=1){
				foreach($buff as $ProtocolNode){
					if(is_a($ProtocolNode,'ProtocolNode')){
						if($ProtocolNode->_tag=="message"){
							$remoteNumber=intval(strstr($ProtocolNode->getAttribute('from'),'@',true));
							$body=$ProtocolNode->getChild('body');
							$media=$ProtocolNode->getChild('media');
							if($this->contactlist->getByNumber($remoteNumber)==false){
								$from=($ProtocolNode->getChild('notify')!=NULL)?$ProtocolNode->getChild('notify')->getAttribute('name'):'Unknown';
								$this->contactlist->createEntry($from,$remoteNumber);
							}
							$remoteUsr=$this->contactlist->getByNumber($remoteNumber);
							if($body!=NULL){
								if($this->lastSender!=$remoteNumber)
									p($remoteUsr->toString().":");
								p($body->_data,"<");
								$this->lastSender=$remoteNumber;
								$this->lastContact=$remoteNumber;
							}
							if($media!=NULL && $media->getAttribute('type')=='location'){
								if($this->lastSender!=$remoteNumber)
									p($remoteUsr->toString().":");
								p("Location: http://maps.google.com/?z=18&ll=".$media->getAttribute('latitude').",".$media->getAttribute('longitude'),"<");
								$this->lastSender=$remoteNumber;
								$this->lastContact=$remoteNumber;
							}
							if($media!=NULL && $media->getAttribute('type')=='vcard'){
								if($this->lastSender!=$remoteNumber)
									p($remoteUsr->toString().":");
								p("Contact: ".$media->getChild('vcard')->getAttribute('name'),"<");
								$this->contactlist->updateEntry($media->getChild('vcard')->getAttribute('name'),array('vcard'=>$media->getChild('vcard')->_data));
								$this->lastSender=$remoteNumber;
								$this->lastContact=$remoteNumber;
							}
							unset($remoteNumber,$body,$media,$from,$remoteUsr);
						}
						elseif($ProtocolNode->_tag=="presence"){
							$entry=$this->contactlist->getByNumber(strstr($ProtocolNode->getAttribute('from'),'@',TRUE));//keep in mind that this is a pointer
							pnl();
							if($entry!=false)
								p($entry->toString()." is ".$ProtocolNode->getAttribute('type'));
							else
								p(strstr($ProtocolNode->getAttribute('from'),'@',TRUE)." is ".$ProtocolNode->getAttribute('type'));
						}
					}
				}
			}
		}
		protected function pullInput(){
			$line = $this->getInput();
			//Interpret input
			if($line != ""){
				if(strrchr($line, " ")){
					// needs PHP >= 5.3.0
					$command = trim(strstr($line, ' ', TRUE));
				}else{
					$command = $line;
				}
				switch($command){
					case "/d":
						$this->debug(!$this->debug);
						break;
					case "/w":
					case "/s":
					case "/send":
					case "/query":
						$query = trim(strstr($line,' ',FALSE));
						if(strpos($query,' ')!==false){//we send a single mesage!
							$tmsg = trim(strstr($query,' ',FALSE));
							$query = trim(strstr($query,' ',TRUE));
							if($this->lastSender!=$sender)
								print("\n[ ] $nickname (Me):\n");
							p("To: ".(($contactlist->get($query)!=false)?$contactlist->get($query)->toString():$query));
							p($tmsg,">");
							$this->wp->Message(time()."-1", $contactlist->getNumber($query), $tmsg);
							$this->lastSender=$sender;
						}
						else{
							$this->dst = $contactlist->getNumber($query);
							p("To: ".(($contactlist->get($query)!=false)?$contactlist->get($query)->toString():$this->dst));
						}
						unset($query,$tmsg);
						break;
					case "/afk":
						$this->status=!$this->status;
						$this->wp->sendPresence($status);
						break;
					case "/r":
					case "/re":
					case "/reply":
						if($this->lastContact==false || !is_int($this->lastContact)){
							px("No one to reply yet.");
							break;
						}
						$query = trim(strstr($line,' ',FALSE));
						if($this->lastSender!=$sender)
							p("$nickname (Me):");
						if($this->dst!=$this->lastContact)
							p("To: ".(($contactlist->get($this->lastContact)!=false)?$contactlist->get($this->lastContact)->toString():$this->lastContact));
						p($query,">");
						$this->wp->Message(time()."-1", $this->lastContact, $query);
						$this->lastSender=$sender;
						unset($query);
						break;
					case "/accountinfo":
						p("Account Info:");
						$acc_info=$this->wp->accountInfo();
						foreach($acc_info as $k=>$v){
							if(strcmp($k,'creation')===0 || strcmp($k,'expiration')===0)
								$v=date("r",$v);
							printf("%10s: %s",$k,$v);
						}
						unset($acc_info,$k,$v);
						break;
					case "/lastseen":
						p("Request last seen ".$this->dst.":");
						p($this->wp->RequestLastSeen($this->dst));
						break;
					case "/cl":
					case "/fl":
					case "/friendlist":
					case "/contactlist":
					case "/addressbook":
						echo("\n[ ] Address book:\n");
						$t=$contactlist->getList();
						foreach($t as $entry)
							echo(sprintf("%10s",(($entry->get('shortcut')!=false)?$entry->get('shortcut'):""))."| ".$entry->toString()."\n");
						break;
					case "/c":
					case "/contact":
						break;
						echo("\n[ ] Choose contact: ");
						//ToDo: switching to contact selection mode
						//		find some nice way... the following code couldn't work
						$user_input=trim(fgets_u(STDIN));
						if($user_input!=""){
							$c=$contactlist->get($user_input);
							if($c==false){
								//new user
								echo("   Creating new user with name: $user_input\n");
								echo("   Phone number: ");
								$number_input=fgets_u(STDIN);
								if(is_int(intval($number_input))){
									$contactlist->createEntry($user_input,$number_input);
									echo("[ ] Contact \"$user_input\" successfully saved!\n");
									break;
								}
								echo("[X] Sorry, something went wrong!");
							}
							else{
								echo("[ ] User selected: ".$c->toString()."\n");
								$cd=$c->getList();
								foreach($cd as $k=>$v){
									printf("%10s: %s\n",$k,$v);
								}
							}
						}
						break;
					case ":q":// for my vim-typo
					case "/q":
					case "/quit":
						//ToDo: sending unavailable status
						$this->setStatus(false);
						//ToDo: cleanup and sending buffer
						die("[X] Good by and have a nice day!\n");
						break;
					default:
						if(!$status)
							$this->setStatus(($status=true));
						if($this->lastSender!=$sender)
							print("\n[ ] $nickname (Me):\n");
						echo "[>] $line\n";
						$this->wp->Message(time()."-1", $this->dst , $line);
						$this->lastSender=$sender;
						break;
				}
			}
		}
		protected function autoupdateStatusUpdate(){
			if($this->autoStatus==true){
				$this->setStatus(true);
				$this->autoStatus=false;
			}
			$this->idlesince=time();
		}
		protected function autoupdateStatus(){
			if($this->autoafk!=0 && $this->autoStatus==false && $this->autoafk<(time() - $this->idlesince)){
				$this->setStatus(false);
				$this->autoStatus=true;
			}
		}
		//helper - small things
		protected function getInput($pStdn=false){
			if($pStdn===false)
				$pStdn=STDIN;
			$pArr = array($pStdn);
			if(false === ($num_changed_streams = stream_select($pArr, $write = NULL, $except = NULL, 0))){
				print("\$ 001 Socket Error : UNABLE TO WATCH STDIN.\n");
				return FALSE;
			}elseif($num_changed_streams > 0){
				$this->autoupdateStatusUpdate();
				return trim(fgets($pStdn, 1024));
			}
		}
		protected function printTimestamp($time=false){
			if(!$time)
				$time=time();
			if($this->lastTimestamp==false)
				p(""," ".date("r",$time)." ");
			elseif(date("H",$this->lastTimestamp)!=date("H",$time))
				p(""," ".date("H:i",$time)." ");
			elseif((date("i",$time)-date("i",$this->lastTimestamp))>=5)
				p(""," ".date("H:i",$time)." ");
			$this->lastTimestamp=$time;
		}
		protected function setStatus($status=true){
			//status of client
			//param status bool (true = available, false = unavailable)
			if($this->wp==false)
				return;
			$this->pd("Setting Status to: ".(($status)?'available':'unavailable'));
			$this->status=$status;
			$this->wp->SendPresence((($status)?'available':'unavailable'));
		}
		protected function debug($debug=true){
			$debug=(($debug)?true:false);
			$this->pd("Disable debug mode!");
			$this->debug=$debug;
			if($this->wp != false)
				$this->wp->debug($debug);
			$this->pd("Debug mode activated!");//only displayed if in debug mode
			pnl();
		}
		protected function pd($string=""){
			if($this->debug)
				print("[!] ".$string."\n");
		}
	}//class
	function p($string="",$pre=" "){
		print("[".$pre."] ".$string."\n");
	}
	function px($string=""){
		print("[X] ".$string."\n");
	}
	function pnl(){
		print("\n\n");
	}
	$wa=new wa();
	$wa->init();
