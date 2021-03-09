<?php if(!defined('ROOT')) die('Access denied.');

class c_questions extends Admin{

	public function __construct($path){
		parent::__construct($path);

		$this->question_filename = ROOT . "config/welive_questions.php";

		$this->CheckAction();
	}


	//更新常见问题缓存
	private function refresh_team_setting(){
		//解决PHP7 Opcache开启时无法实时更新设置的问题
		if(function_exists('opcache_reset')) {
			@opcache_reset();
		}

		//获取数据
		$welive_questions = array();

		$getquestions = APP::$DB->query("SELECT qid, title FROM " . TABLE_PREFIX . "question WHERE activated = 1 ORDER BY sort DESC");
		while($question = APP::$DB->fetch($getquestions)){
			$welive_questions[$question['qid']] = $question['title'];
		}

		$contents = "<?php

//常见问题缓存配置文件

return " . var_export($welive_questions, true) . ";


?>";

		@file_put_contents($this->question_filename, $contents, LOCK_EX);
	}


	//添加
	public function save(){
		$title = ForceStringFrom('title');

		if(!$title) $errors[] = '请填写常见问题内容!';
		if(isset($errors)) Error($errors, '添加常见问题');

		APP::$DB->exe("INSERT INTO " . TABLE_PREFIX . "question (activated, title) VALUES (1, '$title')");

		$lastid = APP::$DB->insert_id;
		APP::$DB->exe("UPDATE " . TABLE_PREFIX . "question SET sort = '$lastid' WHERE qid = '$lastid'");

		$this->refresh_team_setting(); //更新缓存

		Success('questions');
	}

	//添加
	public function add(){
		if(!is_writeable($this->question_filename)){
			$errors = '请将常见问题缓存文件: <br>config/welive_questions.php <br>设置为可写, 即属性设置为: 777';
		}

		if(isset($errors)) Error($errors, '常见问题管理错误');

		SubMenu('添加常见问题', array(array('常见问题列表', 'questions'), array('添加常见问题', 'questions/add', 1)));

		$need_info = '&nbsp;&nbsp;<font class=red>* 必填项</font>';

		echo '<form method="post" action="'.BURL('questions/save').'">';

		TableHeader('常见问题信息:');

		TableRow(array('<b>提示:</b>', '<font class="orange" style="font-size:16px;">1. 常见问题是指访客在对话窗口输入2个或以上字符后, 自动搜索常见问题供访客选择, 方便其快速输入<br>2. 访客如需搜索多个关键字, 可用空格分隔输入内容</font>'));

		TableRow(array('<b>问题内容(不支持html)</b>', '<input type="text" name="title" value="" size="60">' . $need_info));

		TableFooter();

		PrintSubmit('添加常见问题');
	}


	//批量更新常见问题
	public function updatequestions(){
		if(!is_writeable($this->question_filename)){
			$errors = '请将常见问题缓存文件: <br>config/welive_questions.php <br>设置为可写, 即属性设置为: 777';
		}

		if(isset($errors)) Error($errors, '常见问题管理错误');

		$page = ForceIntFrom('p', 1);   //页码
		$search = ForceStringFrom('s');
		$type = ForceIntFrom('t');
		$order = ForceStringFrom('o');

		$need_refresh = 0;

		if(IsPost('updatequestions')){
			$qids = $_POST['qids'];
			$sorts   = $_POST['sorts'];
			$activateds   = $_POST['activateds'];
			$titles   = $_POST['titles'];

			for($i = 0; $i < count($qids); $i++){
				$need_refresh = 1;
				$qid = ForceInt($qids[$i]);
				APP::$DB->exe("UPDATE " . TABLE_PREFIX . "question SET sort = '" . ForceInt($sorts[$i]) . "',
					activated = '" . ForceInt($activateds[$i]) . "',
					title = '" . ForceString($titles[$i]) . "'			
					WHERE qid = '$qid'");
			}
		}else{
			$deleteqids = $_POST['deleteqids'];

			for($i = 0; $i < count($deleteqids); $i++){
				$need_refresh = 1;
				$qid = ForceInt($deleteqids[$i]);
				APP::$DB->exe("DELETE FROM " . TABLE_PREFIX . "question WHERE qid = '$qid'");
			}
		}

		if($need_refresh) $this->refresh_team_setting(); //更新缓存

		Success('questions?p=' . $page. FormatUrlParam(array('s'=>urlencode($search), 't'=>$type, 'o'=>$order)));
	}


	public function index(){
		$NumPerPage = 10;
		$page = ForceIntFrom('p', 1);
		$search = ForceStringFrom('s');
		$type = ForceStringFrom('t');

		if(IsGet('s')) $search = urldecode($search);

		$start = $NumPerPage * ($page-1);

		//排序
		$order = ForceStringFrom('o');
        switch($order)
        {
            case 'activated.down':
				$orderby = " activated DESC ";
				break;

            case 'activated.up':
				$orderby = " activated ASC ";
				break;

            case 'sort.down':
				$orderby = " sort DESC ";
				break;

            case 'sort.up':
				$orderby = " sort ASC ";
				break;

            case 'qid.up':
				$orderby = " qid ASC ";
				break;

			default:
				$orderby = " qid DESC ";			
				$order = "qid.down";
				break;
		}

		SubMenu('常见问题列表', array(array('常见问题列表', 'questions', 1), array('添加常见问题', 'questions/add')));

		TableHeader('搜索常见问题');

		TableRow('<center><form method="post" action="'.BURL('questions').'" name="searchquestions" style="display:inline-block;"><label>关键字:</label>&nbsp;<input type="text" name="s" size="14"  value="'.$search.'">&nbsp;&nbsp;&nbsp;<label>状态:</label>&nbsp;<select name="t"><option value="0">全部</option><option value="1" ' . Iif($type == '1', 'SELECTED') . '>可用</option><option value="2" ' . Iif($type == '2', 'SELECTED') . ' class=red>已禁用</option></select>&nbsp;&nbsp;&nbsp;&nbsp;<input type="submit" value="搜索常见问题" class="cancel"></form></center>');
		
		TableFooter();

		if($search){
			if(preg_match("/^[1-9][0-9]*$/", $search)){
				$s = ForceInt($search);
				$searchsql = " WHERE (qid = '$s' OR sort = '$s' OR title LIKE '%$s%') "; //数字搜索
				$title = "搜索数字号为: <span class=note>$s</span> 的常见问题";
			}else{
				$searchsql = " WHERE (title LIKE '%$search%') ";
				$title = "搜索: <span class=note>$search</span> 的常见问题列表";
			}

			if($type) {
				if($type == 1 OR $type == 2){
					$searchsql .= " AND activated = " . Iif($type == 1, 1, 0)." ";
					$title = "在 <span class=note>" .Iif($type == 1, '有效的常见问题', '已禁用的常见问题'). "</span> 中, " . $title;
				}
			}

		}else if($type){
			if($type == 1 OR $type == 2){
				$searchsql .= " WHERE activated = " . Iif($type == 1, 1, 0)." ";
				$title = "全部 <span class=note>" .Iif($type == 1, '有效的常见问题', '已禁用的常见问题'). "</span> 列表";
			}
		}else{
			$searchsql = '';
			$title = '全部常见问题列表';
		}

		$getquestions = APP::$DB->query("SELECT * FROM " . TABLE_PREFIX . "question ".$searchsql." ORDER BY {$orderby} LIMIT $start,$NumPerPage");

		$maxrows = APP::$DB->getOne("SELECT COUNT(qid) AS value FROM " . TABLE_PREFIX . "question ".$searchsql);

		echo '<form method="post" action="'.BURL('questions/updatequestions').'" name="questionsform">
		<input type="hidden" name="p" value="'.$page.'">
		<input type="hidden" name="s" value="'.$search.'">
		<input type="hidden" name="t" value="'.$type.'">
		<input type="hidden" name="o" value="'.$order.'">';

		TableHeader($title.'('.$maxrows['value'].'个)');
		TableRow(array('<a class="do-sort" for="qid">ID</a>', '<a class="do-sort" for="sort">排序</a>', '<a class="do-sort" for="activated">状态</a>', '问题', '<input type="checkbox" id="checkAll" for="deleteqids[]"> <label for="checkAll">删除</label>'), 'tr0');

		if($maxrows['value'] < 1){
			TableRow('<center><BR><font class=redb>未搜索到任何常见问题!</font><BR><BR></center>');
		}else{
			while($question = APP::$DB->fetch($getquestions)){
				TableRow(array('<input type="hidden" name="qids[]" value="'.$question['qid'].'">' . $question['qid'],

				'<input type="text" name="sorts[]" value="' . $question['sort'] . '" size="4">',

				'<select name="activateds[]"' . Iif(!$question['activated'], ' class=red'). '><option value="1">可用</option><option class="red" value="0" ' . Iif(!$question['activated'], 'SELECTED') . '>禁用</option></select>',

				'<input type="text" name="titles[]" value="' . $question['title'] . '" size="60">',

				'<input type="checkbox" name="deleteqids[]" value="' . $question['qid'] . '">'));
			}

			$totalpages = ceil($maxrows['value'] / $NumPerPage);

			if($totalpages > 1){
				TableRow(GetPageList(BURL('questions'), $totalpages, $page, 10, 's', urlencode($search), 't', $type, 'o', $order));
			}

		}

		TableFooter();

		echo '<div class="submit"><input type="submit" name="updatequestions" value="保存更新" class="cancel" style="margin-right:28px"><input type="submit" name="deletequestions" value="删除常见问题" class="save" onclick="var _me=$(this);showDialog(\'确定删除所选常见问题吗?\', \'确认操作\', function(){_me.closest(\'form\').submit();});return false;"></div></form>
		<script type="text/javascript">
			$(function(){
				var url = "' . BURL("questions") . FormatUrlParam(array('p'=>$page, 's'=>urlencode($search), 't'=>$type)) . '";

				format_sort(url, "' . $order . '");
			});		
		</script>';

	}

} 

?>