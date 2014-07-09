 <?php
        require_once('apns.class.php');
        require_once('db.class.php');
        $db = new DB();

        if(isset($_REQUEST['mode']) && $_REQUEST['mode'] != "")
        {
        	$mode = $_REQUEST['mode'];
        }
        else
        {
        	$mode = 'Development';
        }
        $apns = new APNS($db,$mode);
        switch ($_REQUEST['action'])
        {
        	case 'registerDevices': // 注册设备
        		$args = $_REQUEST;
          		unset($args['action']);
            	$res = $apns->registerDevice($args); 
            	break;
        	case 'updateUserToken': // 更新设备的标识
        		$apns->updateDevice($_REQUEST['devices_token'], $_REQUEST['user_token']);
        		break; 
        	case 'pushMessageToALL': // message:消息内容  type:消息类型
        		$apns->createSSLConnect();
           		$message = $_REQUEST['message'];
           		if (isset($_REQUEST['type']) && $_REQUEST['type'] != "")
           		{
           			$type = $_REQUEST['type'];
            		$apns->sendALLMessage($message,$type);
           		}
           		else 
           		{
           			$apns->sendAllMessage($message);
           		}
            	$apns->closeSSLConnect();
            	if (isset($_REQUEST['source']) && $_REQUEST['type'] != "")
            	{
            		header("Location: ../send/send.html");
            	}
        		break;
        	case 'pushMessageToSingle': // 发送信息到单个用户 message:消息内容 user_token:用户标识
        		$apns->createSSLConnect();
        		$message = $_REQUEST['message'];
        		$usertoken = $_REQUEST['user_token'];
        		if (isset($_REQUEST['type']) && $_REQUEST['type'] != "")
        		{
        			$type = $_REQUEST['type'];
        			$apns->sendSingleMessage($message,$usertoken,$type);
        		}
        		else
        		{
        			$apns->sendSingleMessage($message);
        		}
        		$apns->closeSSLConnect();
        		if (isset($_REQUEST['source']) && $_REQUEST['type'] != "")
        		{
        			header("Location: ../send/sendsingle.html");
        		}
        		break;
        	case 'cleanBadgeNumber': // 清理badge数值
        		$apns->cleanBadgeNumberById($_REQUEST['id'], $_REQUEST['badge']);
        		break;
        	default:
        		$arr = array(
        					'errorcode' => '500',
        					'errormessage' => '推送类型错误'
        				);
        		$json_string = json_encode($arr);
        		echo $json_string;
        		break;
        }
 ?>