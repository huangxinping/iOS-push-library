 <?php 
        class APNS
        {
        	private $ctx;
           	private $ssl_connect;
           	private $push_body;
           	private $ssl_sandbox_url;
           	public $certificate;           
           	private $db;
           	public $mode;
           
           	public function __construct($db,$mode)
           	{
           		require_once 'config.inc.php';
                $this->db = $db;
                $this->mode = $mode;
                if($this->mode == 'Development')
                {
                	$this->certificate = DevelopmentCer;
                	$this->ssl_sandbox_url = DevelopmentSSL;
                }
                else
                {
                	$this->certificate = ProductionCer;               	 
                	$this->ssl_sandbox_url = ProductionSSL;
                }
           	}
         
           	public function registerDevice($args)
           	{
             	if($args != null && $args != array())
             	{
                	$this->db->table = TABLE_NAME;
                	$token = $this->formatToken($args['devices_token']) ;
                	$args['devices_token'] = $token;
	                $args['devices_name'] = str_replace("'", "\”", $args['devices_name']);
	                $mode = $args['mode'];
	                $args['create_time'] = date('Y-m-d H:i:s');
	                $this->mode = $mode;
	               	$res = $this->db->select(array('where'=>"devices_token like '$token' and mode like '$this->mode'"));
		            if($res['row'] == 0)
		            {
		            	$res = $this->db->insert(array('data'=>$args)); 
		            }
		            else
		            {
		            	$where = "devices_token like '$token' and mode like '$this->mode'";
		            	$res = $this->db->update(array('filed'=>array('key'=>'app_version','value'=>$args['app_version']),'where'=>$where)); 
// 		               	$this->cleanBadgeNumberByToken($token, 0);
		            } 
            	}
           	}
           	public function updateDevice($devicestoken,$usertoken)
           	{
           		$this->db->table = TABLE_NAME;
           		$token = $this->formatToken($devicestoken);
           		$where = 'devices_token like "'.$this->formatToken($token).'"';
        		$res = $this->db->update(array('filed'=>array('key'=>'user_token','value'=>$usertoken),'where'=>$where));
           	}
		   	public function formatToken($token)
		   	{
		  	 	return str_replace('<','',str_replace('>', '', str_replace(' ', '', $token)));
		   	}
           	public function createSSLConnect()
           	{
              	$this->ctx = stream_context_create();
              	stream_context_set_option($this->ctx, 'ssl', 'local_cert', $this->certificate);  
              	$this->ssl_connect = stream_socket_client($this->ssl_sandbox_url, $err, $errstr, 60, STREAM_CLIENT_CONNECT, $this->ctx);
          	}
           	public function closeSSLConnect()
           	{
                fclose($this->ssl_connect);
           	}
           	public function cleanBadgeNumberByToken($token,$badge)
           	{
        		$this->db->table = TABLE_NAME;
        		$where = 'devices_token like "'.$this->formatToken($token).'" and mode like "'.$this->mode.'"';
        		$res = $this->db->update(array('filed'=>array('key'=>'badge_number','value'=>$badge),'where'=>$where));
        		return ;
           	}
           	public function cleanBadgeNumberById($id,$badge)
           	{
           	  	$this->db->table = TABLE_NAME;
        		$where = 'id ='.$id;
        		$res = $this->db->update(array('filed'=>array('key'=>'badge_number','value'=>$badge),'where'=>$where));
        		return ;
           	}
           	public function sendALLMessage($message,$type=0,$badge=0,$sound=ReceivedSound)
           	{
            	if(mb_strlen($message,'utf8') > MaxMessageLenght)
            	{
            		$message = mb_substr($message,0,MaxMessageLenght-1,'utf-8')."...";
            	}
              	$this->db->table = TABLE_NAME;
              	$res = $this->db->select(array('filed'=>array('id,devices_token','badge_number'),'where'=>'mode like "'.$this->mode.'" and status = 1'));
              	$this->push_body['aps'] = array('alert'=>$message,'badge'=>$badge,'sound'=>$sound); 
              	if($res['row'] != 0)
              	{
                	foreach ($res['data'] as $value)
                 	{
//   	               		$this->push_body['aps']['badge'] = $value->badge_number + 1;  	
                 		$this->push_body['aps']['badge'] = 1;               
  	               		$this->push_body['aps']['id'] = $value->id;
  	               		$this->push_body['aps']['type'] = $type;
 	          	  		$res =  $this->db->update(array('filed'=>array('key'=>'badge_number','value'=>$value->badge_number+1),'where'=>'mode like "'.$this->mode.'" and status = 1'));
                   		$this->newMessage($value->devices_token);
                	}
              	}
          	}
          	public function sendSingleMessage($message,$usertoken,$type=0,$badge=0,$sound=ReceivedSound)
          	{
          		if(mb_strlen($message,'utf8') > MaxMessageLenght)
            	{
            		$message = mb_substr($message,0,MaxMessageLenght-1,'utf-8')."...";
            	}
            	$this->db->table = TABLE_NAME;
            	$res = $this->db->select(array('filed'=>array('id,devices_token','badge_number'),'where'=>'user_token like "'.$usertoken.'" and status = 1'));
              	$this->push_body['aps'] = array('alert'=>$message,'badge'=>$badge,'sound'=>$sound); 
              	if($res['row'] != 0)
              	{
                	foreach ($res['data'] as $value)
                 	{
//                  			$this->push_body['aps']['badge'] = $value->badge_number + 1;  	
                 			$this->push_body['aps']['badge'] = 1;
  	               			$this->push_body['aps']['id'] = $value->id;
  	               			$this->push_body['aps']['type'] = $type;
 	          	  			$res =  $this->db->update(array('filed'=>array('key'=>'badge_number','value'=>$value->badge_number+1),'where'=>'user_token like "'.$usertoken.'and mode like "'.$this->mode.'" and status = 1'));
                   			$this->newMessage($value->devices_token);
                	}
              	}
          	}
           	public function newMessage($deviceToken,$push_body = null)
           	{
           		if($push_body != null) 
           			$this->push_body = $push_body;
             	$jsonBody = json_encode($this->push_body);
             	$message = chr(0) . pack("n",32) . pack('H*', $deviceToken) . pack("n",strlen($jsonBody)) . $jsonBody; 
             	$this->pushMessage($message);
           	}
           	public function pushMessage($message)
           	{
                fwrite($this->ssl_connect, $message);
           	}
        }
    ?>