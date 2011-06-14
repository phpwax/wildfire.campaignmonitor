<?php

class CMSAdminEmailController extends AdminComponent {
	public $module_name = "email";											
	public $model_class = 'Client';
	public $model_name = "client";													
	public $display_name = "Email";
	
	public $scaffold_columns = array(
    "Subject"   =>array(),
    "SentDate" => array(),
		"TotalRecipients" => array()
  );
  public $filter_columns = false;
	public $allowed_images = false; //no images
	public $allowed_categories = flase; //no categories
	public $edit_author = false; 
	public $extra_content = array(); //extra content fields - runs off the cms_extra_content table
	public $extra_content_options = array(); //corresponding config for the fields
	public $default_order = 'published';
	public $default_direction = 'DESC';
	
	public $show_operations = false;
	public static $base_permissions = array("enabled","menu");
	public static $permissions = array('create');
	
	private function initialise() {
		parent::initialise();
		if($this->model_class) {							
			$this->model = new $this->model_class($this->cm_conf['ClientID']);
		  $this->model_name = Inflections::underscore($this->model_class);
	  }
	}
	
	/**
	* magic method to catch all if the action thats requested doesn't exist
	* this function is used for the section filter drop down; which creates a url like /admin/content/section-url
	* and this converts that into a filtered view of the content by the section specified
	**/
	public function method_missing() {
	  if(!$page = $this->param("page")) $page=1;
		$this->all_rows = $this->model->all();
		$this->filter_block_partial = $this->render_partial("filter_block");
		$this->list = $this->render_partial("list");
	}
	/**
	* main listing page - 
	**/
	public function index() {
	  if(!$page = Request::param("page")) $page=1;		
	  Session::set("list_refer", $_SERVER['REQUEST_URI']);	  
		$this->display_action_name = 'List Campaigns';
		$this->all_rows = $this->model->all();	

	}
	
  public function create(){
    $this->redirect_to("/admin/email/emailcreate/");
  }

	public function emailcreate(){
		$model = $this->model;
		$this->model = new Campaign();		
		$this->model->ClientID = $this->cm_conf['ClientID'];
		Session::unset_var('user_errors'); //remove old errors;
		if($this->model->is_posted()){
			$this->model = $this->model->handle_post();
			$errors ="";		
			if(count($this->model->errors)) $errors .= "<br />".implode("<br />", $this->model->errors);			
			
			if(strlen($errors) > 0){
				$errors .= ":";
				foreach($this->model->errors as $k=> $val) $errors .= $val."<br />";
				Session::add_message('There was an error creating you campaign.'.$errors);
			}else{
				Session::add_message('Your campaign has been created!');
				$this->redirect_to('/admin/email');				
			}
		}		
		$mod = new $this->model_class($this->cm_conf['ClientID']);
		$lists = $mod->GetLists();
		$this->mail_lists = array_merge(array(''=>array('ListID'=>'', 'Name'=>'None')), $lists->rowset);
		$mod = new $this->model_class($this->cm_conf['ClientID']);
		$segments = $mod->GetSegments();
		$this->segments = array_merge(array(''=>array('ListID'=>'', 'Name'=>'None')), $segments->rowset);
		$cont = new CmsContent("published");
		$this->contents = $cont->all();
		
	}
	
	public function view_subscriber(){
		$this->client_lists = $this->model->GetLists();
		if(!$this->list_id = Request::param('id') ){
			$client_list = $this->client_lists->row[0];
			$this->list_id = $client_list['ListID'];
		}	
		Session::set("list_refer", $_SERVER['REQUEST_URI']);
		$this->set_order();
		$this->display_action_name = 'List Subs';
		$this->subs_model	= new Subscriber($this->list_id);
		$this->all_rows = $this->subs_model->all();
		if(!$this->all_rows) $this->all_rows=array();		

		$this->use_view = "index";
		$this->scaffold_columns = array(
	    "Name"   =>array(),
	    "Email" => array(),
			"Date" => array()
	  );
		$this->drop_down_options = $this->client_lists->rowset;
		$this->drop_down_cols = array('key'=>'ListID', 'val'=>'Name');
		
		$this->filter_block_partial = $this->render_partial("filter_block");
		$this->list = $this->render_partial("list");
	}
	
	
	public function view_segments(){		
		Session::set("list_refer", $_SERVER['REQUEST_URI']);
		$this->set_order();
		$this->display_action_name = 'List Seg';
		$this->all_rows = $this->model->GetSegments();
		if(!$this->all_rows) $this->all_rows=array();		
		$this->use_view = "index";
		$this->scaffold_columns = array(
	    "ListID"   =>array('display'=>'Segment ID'),
	    "Name" => array()
	  );
		$this->drop_down_options = false;
		$this->drop_down_cols = false;
		
		$this->filter_block_partial = "";
		$this->list = $this->render_partial("list");
	}
	
	
	
	
}
?>
