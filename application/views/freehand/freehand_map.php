<!DOCTYPE html>
<html>
<head>
<title>KORZHEVDP.COM - Minigis.NET: <?=$title;?></title>
<meta name="keywords" content="<?=$keywords;?>" />
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script type="text/javascript" src="<?=$this->config->item('api');?>/jscript/jquery.js"></script>
<script type="text/javascript" src="<?=$this->config->item('api');?>/jqueryui/js/jqueryui.js"></script>
<script type="text/javascript" src="<?=$this->config->item('api');?>/bootstrap/js/bootstrap.min.js"></script>
<link href="<?=$this->config->item('api');?>/bootstrap/css/bootstrap.css" rel="stylesheet" />
<link href="<?=$this->config->item('api');?>/jqueryui/css/jqueryui.css" rel="stylesheet" />
<link href="<?=$this->config->item('api');?>/css/freehand.css" rel="stylesheet" media="screen" type="text/css" />
</head>
<body>

<!-- навигацыя -->
	<div class="navbar">
		<div class="navbar-inner">
			<a class="brand" href="http://www.korzhevdp.com">KORZHEVDP.COM</a>
			<ul class="nav">
				<li><a href="http://www.korzhevdp.com">Дом</a></li>
				<li class="active"><a href="http://www.korzhevdp.com/projects.html">Проекты</a></li>
				<li><a href="http://works.korzhevdp.com">Работы</a></li>
				<li><a href="http://flood.korzhevdp.com">Проза</a></li>
				<li><a href="http://rock.korzhevdp.com">Музыка</a></li>
			</ul>
			<ul class="nav pull-right">
				<li>
					<ul class="nav">
						<li class="dropdown" style="min-width:180px;">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span id="userP">Гость</span> <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="#" class="myMaps">Мои карты</a></li>
								<li class="divider"></li>
								<li class="logIn"><a href="https://loginza.ru/api/widget?token_url=http://maps.korzhevdp.com/freehand/logindata&lang=ru&providers_set=yandex,facebook,vkontakte" >Войти</a></li>
								<li class="logOut"><a href="http://maps.korzhevdp.com/freehand/logout">Выйти</a></li>
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
					<li id="ehashID"><a href="#" pr=1 title="Показывает ссылку на редактируемую карту">Ссылка на редактируемую карту</a></li>
					<li><a href="#" pr=2 title="Показывает ссылку на нередактируемую карту">Ссылка на нередактируемую карту</a></li>
					<li><a href="#" pr=3 title="Загружает файл с нарисованной интерактивной картой">Скрипт для встраивания на сайт</a></li>
					<li><a href="#" pr=4 title="Выводит содержимое атрибута SRC тега IFRAME">SRC тега IFRAME для встраивания на сайт</a></li>
					<li><a href="#" pr=5 title="Формирует таблицу аннотаций для встраивания">Аннотационная карта (экспериментальная)</a></li>
				</ul>
			</div>
		</div>
	</div>
<!-- навигацыя -->

	<div id="YMapsID"><!-- сам текст -->
		<?=$navigator?>
	</div>



<?=$this->load->view('freehand/freehand_modal_pic',array(),true);?>

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

<!-- <div class="well span6" style="height:400px;position:absolute; top:200px; right:5px;overflow:auto">Консоль<pre><div id="consoleContent"></div></pre></div> -->

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
			url: "/freehand/getmaps",
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
				url: "/freehand/savemapname",
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
<script type="text/javascript" src="<?=$this->config->item('api');?>/jscript/freehand.js"></script>
<script type="text/javascript" src="<?=$this->config->item('api');?>/jscript/map_styles2.js"></script>
<!-- EOT API 2.0 -->
<script src="//loginza.ru/js/widget.js" type="text/javascript"></script>
<!-- <script type="text/javascript" src="<?=$this->config->item('api');?>/ckeditor/ckeditor.js"></script> -->
<?=$footer;?>
</body>
</html>