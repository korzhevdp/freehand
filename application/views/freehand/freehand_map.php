<!DOCTYPE html>
<html>
<head>
<title>Minigis.NET: <?=$title;?></title>
<meta name="keywords" content="<?=$keywords;?>" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script type="text/javascript" src="<?=$this->config->item('api');?>/jscript/jquery.js"></script>
<script type="text/javascript" src="<?=$this->config->item('api');?>/jqueryui/js/jqueryui.js"></script>
<script type="text/javascript" src="<?=$this->config->item('api');?>/bootstrap/js/bootstrap.min.js"></script>
<link href="<?=$this->config->item('api');?>/bootstrap/css/bootstrap.css" rel="stylesheet" />
<link href="<?=$this->config->item('api');?>/jqueryui/css/jqueryui.css" rel="stylesheet" />
<link href="/css/freehand.css" rel="stylesheet" media="screen" type="text/css" />
</head>
<body>

<!-- навигацыя -->
	<!-- <meta name='loginza-verification' content='26e443d51603b20876a5332acd31f007' /> -->
	<div class="navbar">
		<div class="navbar-inner">
			<a class="brand" href="<?=$this->config->item("base_url");?>"><?=$this->config->item("base_url");?></a>
			<ul class="nav pull-right">
				<li>
					<ul class="nav">
						<li class="dropdown" style="min-width:240px;">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span id="userP">Гость</span> <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="#" class="myMaps">Мои карты</a></li>
								<li class="divider"></li>
								<li class="logIn" id="logIn"><a href="#">Войти как пользователь</a></li>
								<!-- <li class="logIn"><a href="https://loginza.ru/api/widget?token_url=<?=$this->config->item('base_url');?>login/logindata&lang=ru&providers_set=yandex,facebook,vkontakte" >Войти</a></li> -->
								<li class="logOut" id="logOut"><a href="<?=$this->config->item('base_url');?>login/logout">Выйти</a></li>
							</ul>
						</li>
					</ul>
				</li>
			</ul>
		</div>
	</div>

	<div class="well mapName map_name">
		<input type="text" name="mapName" id="mapName" class="pull-left" placeholder="ID карты..." title="Введите сюда ID карты">
		<!-- <button type="button" class="btn btn-mini btn-primary">Сохранить имя</button> -->
		<div class="btn-toolbar" style="margin: 0;">
			<div class="btn-group" style="margin-left: 5px;">
				<a class="btn dropdown-toggle btn-small btn-info" style="margin-top:2px;" data-toggle="dropdown" href="#">Карта&nbsp;<span class="caret"></span></a>
				<ul class="dropdown-menu">
					<li><a href="#" id="mapLoader" title="Показывает карту с указанным идентификатором">Загрузить</a></li>
					<li><a href="#" id="mapSave" title="Запоминает внесённые изменения">Обработать</a></li>
					<li class="divider"></li>
					<li><a href="#" id="mapReset" title="Очищает список объектов">Новая карта</a></li>
				</ul>
			</div>
			<div class="btn-group pull-right">
				<a class="btn dropdown-toggle btn-small btn-success" style="margin-top:2px;" data-toggle="dropdown" href="#" title="Поделиться картой">Поделиться&nbsp;<span class="caret"></span></a>
				<ul id="linkFactory" class="dropdown-menu">
					<li id="ehashID"><a href="#" pr=1 title="Показывает ссылку на редактируемую карту">Редактируемая карта</a></li>
					<li><a href="#" pr=2 title="Показывает ссылку на нередактируемую карту">Нередактируемая карта</a></li>
					<li><a href="#" pr=3 title="Загружает файл с нарисованной интерактивной картой">JS Скрипт</a></li>
					<li><a href="#" pr=4 title="Выводит содержимое атрибута SRC тега IFRAME">SRC тега IFRAME</a></li>
					<li><a href="#" pr=5 title="Формирует таблицу аннотаций для встраивания">Аннотационная карта</a></li>
					<li><a href="#" pr=6 title="Импортирует типизированные объекты">Импорт</a></li>
				</ul>
			</div>
		</div>
	</div>
<!-- навигацыя -->

	<div id="YMapsID"><!-- сам текст -->
		<?=$navigator?>
	</div>

<?=$this->load->view('freehand/freehand_modal_pic', array(), true);?>

<div class="modal hide" id="myMapsM" style="width:640px;">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>Мои карты</h3>
	</div>
	<div class="modal-body" style="height:460px;">
		<table class="table table-bordered table-striped table-condensed table-hover">
			<tbody>
			<tr>
				<th style="width:210px;">Название</th>
				<th style="width:180px;">ID</th>
				<th style="width:50px;" title="Карта может быть добавлена в публичный каталог схем">Публичная</th>
				<th style="width:40px;" title="Сохранить карту">Сохр</th>
			</tr>
			</tbody>
			<tbody id="myMapList"></tbody>
		</table>

	</div>
	<div class="modal-footer">
		<button type="button" class="btn" data-dismiss="modal">Закрыть</button>
		<!-- <button type="button" id="saveMaps" class="btn btn-primary" data-dismiss="modal">Готово</button> -->
	</div>
</div>

<div class="modal hide" id="transferM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>Текст кода обмена</h3>
	</div>
	<div class="modal-body" id="transferCode"></div>
	<div class="modal-footer">
		<button type="button" class="btn" data-dismiss="modal">Закрыть</button>
	</div>
</div>

<div class="modal hide" id="loginM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>Войти как пользователь</h3>
	</div>
	<div class="modal-body">
	<div class="alert alert-info" id="logAlert">Введите имя пользователя и пароль. Если при проверке реквизитов пользователь не будет найден, вам будет предложено зарегистрироваться</div>
	Пользователь: <input type="text" id="login"><br>
	<div class="alert alert-info hide" id="regWelcome">Пользователь не найден. Вы можете зарегистрироваться, введя новые имя пользователя и пароль.<button type="button" class="btn btn-info btn-block" id="doNotReg">Спасибо, но я попробую ввести пароль ещё раз</button></div>
	Пароль:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="password" id="password"><br>
	<span class="hide" id="password2span">Пароль ещё раз: <input type="password" id="password2"></span>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-primary hide" id="tryRegIn">Зарегистрироваться</button>
		<button type="button" class="btn btn-primary" id="tryLogIn">Войти</button>
		<button type="button" class="btn" data-dismiss="modal">Закрыть</button>
	</div>
</div>

<div class="modal hide" id="importM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h3>Импорт объектов</h3>
	</div>
	<div class="modal-body" id="transferCode2">
		<textarea name="importCode" id="importCode" rows="12" cols="80" style="width:518px; height:450px;"></textarea>
	</div>
	<div class="modal-footer">
		<input type="checkbox" id="cRev"> Реверс координат
		<button type="button" class="btn" data-dismiss="modal">Закрыть</button>
		<button type="button" class="btn btn-primary" id="importBtn">Импорт</button>
	</div>
</div>

<div class="well hide container-fluid" id="mapLinkContainer" style="height:28px;padding:5px;position:absolute;top:45%; left:30%; width:580px;">
	<input type="text" name="mapLink" id="mapLink" value="" style="width:480px;" class="pull-left">
	<button type="button" class="btn btn-small btn-primary pull-right" id="linkClose" style="margin-top:2px;">Закрыть</button>
</div>

<div class="hide"><?=$links_heap;?></div>

<div style="display:none;">
	<input type="hidden" name="maphash"      id="maphash"      value="<?=$maphash;?>">
	<input type="hidden" name="current_zoom" id="current_zoom" value="<?=$zoom;?>">
	<input type="hidden" name="current_type" id="current_type" value="<?=$maptype;?>">
	<input type="hidden" name="map_center"   id="map_center"   value="<?=$maps_center;?>">
	<input type="hidden" name="current_obj_type" id="current_obj_type" value="1">
	<input type="hidden" name="gCounter"     id="gCounter"     value="<?=$gcounter;?>">
	<input type="hidden" name="location_id"  id="location_id"  value="">
</div>

<script type="text/javascript">
	<!--

	$(".modal").modal("hide");

	$(".logIn").click(function () {
		$("#loginM").modal("show");
	});

	$("#doNotReg").click(function(){
		$("#password2span, #regWelcome, #tryRegIn, #logAlert").addClass("hide");
		$("#tryLogIn").removeClass("hide");
	});

	$("#tryLogIn").click(function(){
		$.ajax({
			url          : "/locallogin/checkuser",
			type         : "POST",
			data         : {
				login    : $("#login").val(),
				password : $("#password").val(),
			},
			dataType     : 'script',
			success: function () {
				if (parseInt(logresult.status, 10) === 0) {
					$("#password2span, #regWelcome, #tryRegIn").removeClass("hide");
					$("#tryLogIn, #logAlert").addClass("hide");
				}
				if (parseInt(logresult.status, 10) === 1) {
					$("#userP").html(logresult.login + '&nbsp;&nbsp;<i class="icon-user"></i>');
					$("#password2span, #regWelcome, #tryRegIn, #logOut").removeClass("hide");
					$("#tryLogIn, #logAlert, #logIn").addClass("hide");
					$("#loginM").modal("hide");
				}
			},
			error: function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	});


	
	$("#tryRegIn").click(function(){
		$.ajax({
			url           : "/locallogin/adduser",
			type          : "POST",
			data          : {
				login     : $("#login").val(),
				password  : $("#password").val(),
				password2 : $("#password2").val()
			},
			dataType      : 'script',
			success: function () {
				if (regresult.status === 1) { 
					$("#userP").html(regresult.login);
					$("#loginM").modal("hide");
				}
				if (regresult.status === 0) { 
					console.log(regresult.error);
				}
			},
			error: function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	});

	$(function() {
		$("#SContainer").draggable({containment: "#YMapsID", scroll: false, handle: "#YMHead" });
	});
	
	$(function() {
		$(".modal").draggable({ containment: "body", scroll: false, handle: ".modal-header" });
	});

	$(function(){
		$('.mapName, #SContainer').delay(20000).animate({ opacity: 0.4 }, 2000, 'swing', function(){});
	});

	$('#SContainer').mouseleave(function(){
		$(this).delay(30000).animate({opacity: 0.4}, 2000, 'swing', function(){});
	});

	$('#SContainer').mouseenter(function(){
		$(this).dequeue().stop().animate({opacity: 1}, 200);
	});

	$('.map_name').mouseleave(function(){
		$(this).delay(20000).animate({opacity: 0.2}, 2000, 'swing', function(){});
	});

	$('.map_name').mouseenter(function(){
		$(this).dequeue().stop().animate({opacity: 1}, 100);
	});

	function get_user(){
		$.ajax({
			type: "POST",
			url: "/freehand/getuserdata",
			dataType: 'script',
			success: function(data){
				data = eval(data);
				$("#userP").attr('title', data[2]);
				$("#userP").html(data[0] + '&nbsp;&nbsp;' + data[1]);
				if(data[0] == "Гость"){
					$(".logIn").removeClass("hide");
					$(".logOut").addClass("hide");
				}else{
					$(".logOut").removeClass("hide");
					$(".logIn").addClass("hide");
				}
			},
			error: function(data,stat,err){
				alert([data,stat,err].join("\n"));
			}
		});
	}

	get_user();

	$('#YMHead').dblclick(function() {
		if($('#navigator').css('display') == 'block'){
			$('#navigator, #navheader').css('display', 'none');
			$('#SContainer').css('height', 27);
		}else{
			$('#navigator, #navheader').css('display', 'block');
			$('#SContainer').css('height', 340);
		}
	});

	$('#navup').unbind().click(function() {
		$('#navigator, #navheader').css('display', 'none');
		$(this).css('display', 'none');
		$('#navdown').css('display', 'block');
		$('#SContainer').css('height', 27);
	});

	$('#navdown').unbind().click(function() {
		$('#navigator, #navheader').css('display', 'block');
		$(this).css('display', 'none');
		$('#navup').css('display', 'block');
		$('#SContainer').css('height', 340);
	});
	
	$("#pointfilter").keyup(function(){
		if($("#pointfilter").val().length){
			$(".mg-btn-list").each(function(){
				var test = $(this).html().toString().toLowerCase().indexOf($("#pointfilter").val().toString().toLowerCase()) + 1;
				(test) ? $(this).parent().css('display','block') : $(this).parent().css('display','none');
			});
		}
	});
	
	$(".myMaps").click(function(e){
		e.preventDefault();
		$.ajax({
			url: "/mapmanager/getmaps",
			type: "GET",
			dataType: "html",
			success: function(data){
				$("#myMapList").empty().append(data);
				$("#myMapsM").modal("show");
				renamerListen();
			},
			error: function(data,stat,err){
				//$("#consoleContent").html([data,stat,err].join("<br>"));
				alert([data,stat,err].join("\n"));
			}
		});
	});

	function renamerListen(){
		$(".userMapNameSaver").unbind().click(function(){
			ref = $(this).attr("ref");
			$.ajax({
				url: "/mapmanager/savemapname",
				type: "POST",
				data : {
					uhash : ref,
					name  : $(".userMapName[ref=" + ref + "]").val(),
					pub   : ($(".userMapPublic[ref=" + ref + "]").prop("checked")) ? 1 : 0
				},
				dataType: "html",
				success: function(data){
					$(this).addClass("btn-success");
					console.log(data);
				},
				error: function(data,stat,err){
					//$("#consoleContent").html([data,stat,err].join("<br>"));
					alert([data,stat,err].join("\n"));
				}
			});
		});
	}

	$("#saveMaps").click(function(){
		$("#mapForm").submit();
	});


	$('#modal_pics').modal({ show: 0 });

	$('#modal_pics').on('show', function () {
		carousel_init();
	});

	$("#YMapsID").height($(window).height() - 52 + 'px');
	$("#YMapsID").width($(window).width() - 4 + 'px');
//-->
</script>
<!-- API 2.0 -->
<script src="http://api-maps.yandex.ru/2.0/?coordorder=longlat&amp;load=package.full&amp;mode=debug&amp;lang=ru-RU" type="text/javascript"></script>
<script type="text/javascript" src="<?=$this->config->item('api');?>/jscript/styles2.js"></script>
<script type="text/javascript" src="<?=$this->config->item('api');?>/jscript/yandex_styles.js"></script>
<script type="text/javascript" src="/jscript/freehand.js"></script>

<!-- EOT API 2.0 -->
<script src="//loginza.ru/js/widget.js" type="text/javascript"></script>
<!-- latest version available at WWW.KORZHEVDP.COM -->
<?=$footer;?>
</body>
</html>