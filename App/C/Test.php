<?PHP


class C_Test extends Qee_Controller_Action
{
	public function actionIndex()
	{
		//echo DONE_INDEX;
		print('test index');
		return ;
	}
	
	/**
	 * function description
	 * 
	 * @param
	 * @return void
	 */
	public function actionList($page = 1)
	{
		echo "list for Page: ", $page;
		return ;
	}
	
	/**
	 * function description
	 * 
	 * @param
	 * @return void
	 */
	public function actionView($id = 0)
	{
		if($id)
		{
			printf("view for '%i'", $id);
		}
		else
		{
			print("view nothing");
		}
		return ;
	}
	
}