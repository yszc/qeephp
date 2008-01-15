<?PHP


class C_Home extends Qee_Controller_Action
{
	public function actionIndex()
	{
		//echo DONE_INDEX;
		print('home');
		$data = array('title'=>'Test App Home');
		$this->_executeView('home.htm', $data);
		return ;
	}
	
	/**
	 * function description
	 * 
	 * @param
	 * @return void
	 */
	public function actionSearch($key = '')
	{
		if(!empty($key))
		{
			printf("searching for '%s'", $key);
		}
		else
		{
			print("search nothing");
		}
		return ;
	}
	
}
