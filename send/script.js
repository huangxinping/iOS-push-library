function checkValid()
{
	var a = document.pushMessage.message;
	if(a.value.length <= 0)
	{
	   window.alert('推送内容不能小于1个字符');
	   event.returnValue = false;
	   return false;
	} 
	return true;
}