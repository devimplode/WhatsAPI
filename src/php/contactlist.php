<?php

class contactlist {
	protected $contactlist=array();
	protected $edited=array();
	protected $defaultAddressBook=false;

	public function __construct($addressBook=false){
		if($addressBook!=false){
			if((stripos($addressBook,'/')===false)&&(stripos($addressBook,'\\')===false))
				$addressBook=dirname(__FILE__).DIRECTORY_SEPARATOR.$addressBook;
			$this->defaultAddressBook=$addressBook;
		}
		else
			$this->defaultAddressBook=dirname(__FILE__).DIRECTORY_SEPARATOR."addressbook";
		$this->readAddressbook($this->defaultAddressBook);
	}
	public function readAddressbook($file){
		if(!is_file($file))
			return false;
		$db=parse_ini_file($file,false);
		foreach($db as $k=>$v){
			if(!isset($this->contactlist[$file]))
				$this->contactlist[$file]=array();
			$this->contactlist[$file][]=unserialize($v);
		}
		$edited[$file]=false;
	}
	public function getList(){
		$ret=array();
		foreach($this->contactlist as $addressbook)
			foreach($addressbook as $entry)
				$ret[]=$entry;
		return $ret;
	}
	public function get($name){
		if($this->getByShortcut($name))
			return $this->getByShortcut($name);
		elseif($this->getByName($name))
			return $this->getByName($name);
		elseif($this->getByNumber($name))
			return $this->getByNumber($name);
		else
			return false;
	}
	public function getNumber($name){
		return ($this->get($name) instanceOf contact)?$this->get($name)->get('number'):$name;
	}
	public function getByName($name){
		foreach($this->contactlist as $addressbook)
			foreach($addressbook as $entry)
				if(strcmp($entry->get('name'),$name)===0)
					return $entry;
		return false;
	}
	public function getByShortcut($name){
		foreach($this->contactlist as $addressbook)
			foreach($addressbook as $entry)
				if(strcmp($entry->get('shortcut'),$name)===0)
					return $entry;
		return false;
	}
	public function getByNumber($name){
		foreach($this->contactlist as $addressbook)
			foreach($addressbook as $entry)
				if(strcmp($entry->get('number'),$name)===0)
					return $entry;
		return false;
	}
	public function createEntry($name,$number,$shortcut='',$addressbook=false,$data=false){
		if($addressbook==false)
			$addressbook=$this->defaultAddressBook;
		$entry=new contact(array('name'=>$name,'number'=>$number,'shortcut'=>$shortcut));
		if(is_array($data))
			foreach($data as $k=>$v)
				$entry->set($k,$v);
		$this->contactlist[$addressbook][]=$entry;
		$this->edited[$addressbook]=true;
		$this->write();
		return $entry;
	}
	public function updateEntry($name,$data,$addressbook=false){
		if($addressbook==false)
			$addressbook=$this->defaultAddressBook;
		if($this->getByName($name)!=false){
			$contact=$this->getByName($name);
			foreach($data as $k=>$v)
				if(is_string($k) && is_string($v))
					$contact->set($k,$v);
		}
		elseif(isset($data['name']) && isset($data['number']) && isset($data['shortcut']))
			$this->createEntry($data['name'],$data['number'],$data['shortcut'],$addressbook);
		elseif(isset($data['name']) && isset($data['number']))
			$this->createEntry($data['name'],$data['number'],'',$addressbook);
		elseif(isset($data['number']))
			$this->createEntry($name,$data['number'],'',$addressbook);
		else
			return false;
		$this->write();
		return true;
	}
	protected function write(){
		foreach($this->contactlist as $file=>$addressbook){
			if(!isset($this->edited[$file]) || $this->edited[$file]!=true)
				continue;
			$out="";
			foreach($addressbook as $k=>$entry){
				$out.=$k." = \"".str_replace('"','\"',serialize($entry))."\"".PHP_EOL;
			}
			file_put_contents($file,$out,LOCK_EX);
			$this->edited[$file]=false;
		}

	}
	public function __destruct(){
		$this->write();
	}
}

class contact{
	protected $name, $number, $shortcut=false;
	public function __construct($data=false){
		if(is_array($data) && isset($data['name']) && isset($data['number'])){
			$this->name=$data['name'];
			$this->number=$data['number'];
			if(isset($data['shortcut']))
				$this->shortcut=$data['shortcut'];
		}
		else
			throw new Exception("Wrong data for new contact!");
	}
	public function get($attribute){
		if(isset($this->$attribute) && $this->$attribute!==false)
			return $this->$attribute;
		return false;
	}
	public function getList(){
		$ret=array();
		foreach($this as $k=>$v)
			$ret[$k]=$v;
		return $ret;
	}
	public function toString(){
		if(isset($this->name) && isset($this->number))
			return $this->name." (".$this->number.")";
		return false;
	}
	public function set($attribute,$value){
		$this->$attribute=$value;
	}
}
?>
