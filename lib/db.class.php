<?php
	date_default_timezone_set('PRC');
	class DB
	{
		public $conn;
		public $table;
		private $res;
		private $result ;
		private  $error;
		public function __construct()
		{
			$this->connect();
		}
			
		public function connect()
		{
			require_once 'config.inc.php';
			$this->conn = mysql_connect(DB_HOST,DB_USER,DB_PWD) or $this->error = 'mysql连接失败';	
			 mysql_select_db(DB_NAME, $this->conn) or ($this->error == ""?$this->error = "mysql数据库绑定失败":"");
       		 mysql_query("SET NAMES ".DB_CODE);
       		 echo $this->error;
		}
		
		public function query($sql,$m = 1)
		{
			$this->result = array();
			
			$this->res = mysql_query($sql,$this->conn);
             
             if(mysql_error() != "")
             	$this->result['error'] = mysql_error();

              $this->result['sql'] = $sql;
             
              if($m == 1)
              {
           	   while(@$array = mysql_fetch_object($this->res))
	              {
	            	$this->result['data'][] = $array;
	              } 
              }
              else 
              {
              	  while($array = mysql_fetch_array($this->res))
	              {
	            	$this->result['data'][] = $array;
	              }
              }
		        @$this->result['row'] = mysql_num_rows($this->res);
		        return $this->result;  
		}
		
		public function getParm($key,&$parm)
		{
			if(is_array($parm) && array_key_exists($key, $parm)  )			
				return true;
			else 
				return false;
		}
		
		public function select($parm = "")
		{			  
			 $count = $this->getParm('count', $parm) ? $parm['count'] : "";
			 if($count == "")
			   $filed = $this->getParm('filed', $parm) ? implode(',', $parm['filed']) : "*";
			 else
			 	$filed = $count;
			 $where = $this->getParm('where', $parm) ? " where  ". $parm['where'] : "";
			 $order = $this->getParm('order',$parm ) ? $parm['order']  : "";
			 $orderBy =  $this->getParm('orderBy',$parm ) ? $parm['orderBy']  : "";
			 $limit = $this->getParm('limit',$parm ) ? $parm['limit']  : 0;
		     $start = $this->getParm('start',$parm) ? $parm['start']  : 0;
		     
			 $sql = "SELECT ".$filed." FROM ".$this->table." ".$where;
			 
			 if($order != "")			 
			   @$sql .= " ORDER BY " . implode(',', $order) . " $order" . " $orderBy";

		 	if ($limit > 0)
	            if ($start > 0)
	                $sql .= " LIMIT " . $start . "," . $limit;
	            else 
	                $sql .= " LIMIT " . $limit;
	                
            return  $this->query($sql);
		}
		
		public function update($parm = "")
		{
			$filed = $this->getParm('filed', $parm) ? array('key'=>$parm['filed']['key'],'value'=>$parm['filed']['value']): "";
			$where = $this->getParm('where', $parm) ? " WHERE  ". $parm['where'] : "";
 
			 $this->result = array();
			 	if(!isset($filed['key']) )
			 	{
			 		$this->result['error'] = '缺少参数';
			 	}
			  
 		 		$sql = "UPDATE ".$this->table." SET ".$filed['key'] . " = " . "'$filed[value]'" . $where;
 		 		$this->res = mysql_query($sql);
				
				if( mysql_error() != "")
					$this->result['error'] = mysql_error();
				else
				{
					$arr = array('sql'=>$sql,'attect' => mysql_affected_rows());
					$this->result['res'][] = $arr;
				}				
			 
			return $this->result;
		}
		
		public function execSql($sql,$m="")
		{
			$this->res = mysql_query($sql,$this->conn);
			if(mysql_error() != "")
				$this->result['error'] = mysql_error();
			else 
			{
				if($m == "")
				{
					 $this->result['affect'] = mysql_affected_rows();
				}
				else
				{
					 $this->result['insertId']  = mysql_insert_id();
					 $this->result['affect'] = mysql_affected_rows();
				}
			}
			 return $this->result;	
		}
		
		public function insert($parm = "")
		{
			 $this->result = array();
			 
			 if($parm == "" || $parm === array())
			 {
				$this->result['error'] = '缺少参数';
				return $this->result;
			 }
			 
			 $key = $this->getParm('data', $parm) ?  implode(",", array_keys($parm['data'])) : "";
			 $value = $this->getParm('data', $parm) ?   implode("','", array_values($parm['data'])) : "";
		
			 if($key == "" || $value == "")
			 {
				$this->result['error'] = '参数错误';
				return $this->result;
			 }
			 
		  	$sql = "INSERT INTO `".$this->table . "` (" . $key. ")"." VALUES ". "('" . $value."')"; 
			return $this->execSql($sql,'insert');			 
		}
		
		public function delete($parm = "")
		{
			$where = $this->getParm('where', $parm) ? " WHERE  ". $parm['where'] : "";
		 	$sql = "DELETE FROM " . $this->table . $where;
			return $this->execSql($sql);
		}
		
		public function __destruct()
		{
       		 mysql_close($this->conn);   	
		}
	}
?>