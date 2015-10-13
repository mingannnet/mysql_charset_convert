<?php
class MYSQL_ENCODING_CONVERT {
	static private $db_src=array(
		'server'=>'127.0.0.1',
		'account'=>'test1',
		'password'=>'test2',
		'dbname'=>'database1',
	);

	/**
	 * user:test2 must has create table permission
	 */
	static private $db_dst=array(
		'server'=>'127.0.0.1',
		'account'=>'test2',
		'password'=>'test2',
		'dbname'=>'database2',
	);

	static private $conn_src=NULL;
	static private $conn_dst=NULL;

	static private $table_create=0;

	/**
	 * init 
	 * 
	 * @param int $create_table ceate table first or not
	 * @static
	 * @access public
	 * @return void
	 */
	static public function init($table_create=0){
		echo 'Start'."\n";
		self::$table_create=$table_create;
		self::db_connect();
		self::data_convert();
	}


	static private function db_connect(){
		self::$conn_src=new PDO('mysql:dbname='.self::$db_src['dbname'].';host='.self::$db_src['server'],
			self::$db_src['account'],
			self::$db_src['password'],
			array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8')) or die('Error 1: connect to db_src.');
		self::$conn_dst=new PDO('mysql:dbname='.self::$db_dst['dbname'].';host='.self::$db_dst['server'],
			self::$db_dst['account'],
			self::$db_dst['password'],
			array(PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES UTF8')) or die('Error 2: connect to db_dst.');
	}


	static private function data_convert(){
		$tables=self::table_list('src');
		for($i=0,$n=count($tables);$i<$n;$i++){
			if(isset($meta['auto_increment'])){
				$sql='SELECT * FROM `'.$tables[$i].'` ORDER BY `'.$meta['auto_increment'].'`';
			}
			else{
				$sql='SELECT * FROM `'.$tables[$i].'`';
			}
			$bind=array();
			$fields=array();
			$rs=self::$conn_src->query($sql);
			$row=$rs->fetch(PDO::FETCH_ASSOC);
			$fields[]=array_keys($row);
			for($j=0,$m=count($fields);$j<$m;$j++){
				$binds[':'.$fields[$j]]=$row[$fields[$j]];
			}
			$sql='INSERT INTO `'.$tables[$i].'`(`'.join('`,`',$fields).') VALUES('.join(',',array_keys($binds)).')';
			$rs=$conn_dst->prepare($sql);
			$rs->execute($binds);
			while($row=$rs->fetch(PDO::FETCH_ASSOC)){
				for($j=0;$j<$m;$j++){
					$binds[':'.$fields[$j]]=$row[$fields[$j]];
				}
				$rs->execute($binds);
			}
		}
	}

	static private function table_list($db='src'){
		$tables=array();
		$sql='show tables';
		$res=self::${'conn_'.$db}->query($sql);
		while($table=$res->fetch(PDO::FETCH_ASSOC)){
			$tables[]=$table;
		}
		return $tables;
	}

	static private function table_meta($table,$db='src'){
		$fields=array();
		$sql='DESCRIBE '.$table;
		$res=self::${'conn_'.$db}->query($sql);
		while($field=$res->fetch(PDO::FETCH_ASSOC)){
			$r=strpos($field['Type'],'(');
			$type2='';
			if($r>1){
				$charset=substr($field['Type'],$r);
				if(in_array($charset,array('enum','set'))){
					$type2=substr($r+1,-1);
				}
				else if(in_array($charset,array('decimal'))){
					$type2=substr($r+1,-1);
				}
				else{
					$type2=substr($r+1,-1);
				}
			}
			$fields[]=array(
				'field'=>$field['Field'],
				'charset'=>$charset,
				'option'=>$type2,
				'allow_null'=>strcasecmp($field['Null'],'NO')?0:1,
				'default'=>$field['Default'],
				'auto_increment'=>$field['Extra']
			);
			if($field['Extra']=='auto_increment'){
				$pk=$field['Extra'];
			}
		}
		return array('fields'=>$fields,'auto_increment'=>$pk);
	}

}
MYSQL_ENCODING_CONVERT::init(1);

