<!DOCTYPE html>
<html>
<head>
<title id="headTitle">MiniGis Freehand</title>
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
			<a class="brand" href="<?=$this->config->item("base_url");?>"><?=$this->config->item("brand");?></a>
			<ul class="nav">
				<li><a href="#" id="uiFrameName"></a></li>
			</ul>

			<ul class="nav pull-right">
				<li>
					<ul class="nav">
						<li class="dropdown">
							<a href="" class="dropdown-toggle" data-toggle="dropdown"><i class="icon-list-alt" title="Свойства проекта"></i></a>
							<ul class="dropdown-menu">
								<li id="propertiesShow" title="Название, состав фреймов и т.д."><a href="#">Свойства карты</a></li>
								<li id="objectManagerShow" title="Управление объектами карты"><a href="#">Менеджер объектов</a></li>
							</ul>
						</li>
					</ul>
				</li>

				<li id="prevFrame" class="frameSwitcher" ref="-1">
					<a href="#"><i class="icon-chevron-left"></i></a>
				</li>
				<li class="input-append input-prepend">
					<input type="text" id="frameNum" value="1" readonly="readonly" maxlength=3 style="width:25px;margin-top:6px;margin-bottom:0px;">
				</li>
				<li id="nextFrame" class="frameSwitcher" ref="1">
					<a href="#"><i class="icon-chevron-right"></i></a>
				</li>

				<li id="searchFormToggle">
					<a href="#"><i class="icon-search"></i></a>
				</li>

				<li>
					<ul class="nav">
						<li class="dropdown" style="min-width:240px;">
							<a href="#" class="dropdown-toggle" data-toggle="dropdown"><span id="userP">Гость</span> <b class="caret"></b></a>
							<ul class="dropdown-menu">
								<li><a href="#" class="myMaps">Мои карты</a></li>
								<li class="divider"></li>
								<li class="logIn" id="logIn"><a href="#">Войти как пользователь</a></li>
								<li class="logOut" id="logOut"><a href="<?=$this->config->item('base_url');?>locallogin/logout">Выйти</a></li>
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
					<li><a href="#" id="mapNew" title="Очищает список объектов и создаёт новую карту">Новая карта</a></li>
					<li><a href="#" id="mapLoader" title="Показывает карту с указанным идентификатором">Загрузить</a></li>
					<li><a href="#" id="mapSave" title="Запоминает внесённые изменения">Сохранить</a></li>
					<li><a href="#" id="mapDelete" title="Удалить карту">Удалить</a></li>
				</ul>
			</div>
			<div class="btn-group pull-right">
				<a class="btn dropdown-toggle btn-small btn-success" style="margin-top:2px;" data-toggle="dropdown" href="#" title="Поделиться картой">Поделиться&nbsp;<span class="caret"></span></a>
				<ul id="linkFactory" class="dropdown-menu">
					<li id="ehashID"><a href="#" pr=1 title="Показывает ссылку на редактируемую карту">Редактируемая карта</a></li>
					<li><a href="#" pr=2 title="Показывает ссылку на нередактируемую карту">Нередактируемая карта</a></li>
					<li><a href="#" pr=3 title="Загружает файл с нарисованной интерактивной картой">Сохранить в HTML</a></li>
					<li><a href="#" pr=4 title="Выводит содержимое атрибута SRC тега IFRAME">SRC тега IFRAME</a></li>
					<li class="divider"></li>
					<!--
					<li><a href="#" pr=5 title="Экспортирует объекты карты во внутреннем формате обмена">Экспорт</a></li>
					<li><a href="#" pr=6 title="Импортирует объекты из внутреннего формата обмена">Импорт</a></li>
					-->
					<li><a href="#" pr=7 title="Экспортирует объекты карты в GeoJSON">Экспорт в GeoJSON</a></li>
					<li><a href="#" pr=7 title="Импортирует объекты на карту из GeoJSON">Импорт из GeoJSON</a></li>
				</ul>
			</div>
		</div>
	</div>
	<!-- навигацыя -->

	<div id="YMapsID"><!-- сам текст -->
		<?=$navigator?>
	</div>

<div class="modal hide" id="myMapsM" style="width:740px;">
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
		<h4>Текст кода обмена</h4>
	</div>
	<div class="modal-body" id="transferCode"></div>
	<div class="modal-footer">
		<button type="button" class="btn" data-dismiss="modal">Закрыть</button>
	</div>
</div>

<div class="modal hide" id="loginM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4>Войти как пользователь</h4>
	</div>
	<div class="modal-body">
	<div class="alert alert-info" id="logAlert">Введите имя пользователя и пароль. Если при проверке реквизитов пользователь не будет найден, вам будет предложено зарегистрироваться</div>
	Пользователь: <input type="text" id="login"><br>
	<div class="alert alert-info hide" id="regWelcome">Пользователь не найден. Вы можете зарегистрироваться, введя новые имя пользователя и пароль.<button type="button" class="btn btn-info btn-block" id="doNotReg">Спасибо, но я попробую ввести пароль ещё раз</button></div>
	Пароль:&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="password" id="password">&nbsp;&nbsp;<small style="color:red"><span class="hide" id="wrongPass"></span></small><br>
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
		<h4>Импорт объектов</h4>
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

<div class="modal hide" id="imageM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4>Выбрать фотографии</h4>
	</div>
	<div class="modal-body">
		<ul id="imageList"></ul>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn btn-primary" id="submitSelection">Готово</button>
	</div>
</div>

<div class="modal hide" id="viewerM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4>Фотографии объекта</h4>
	</div>
	<div class="modal-body">
		<img src="/images/nophoto.jpg" id="locImg" border="0" alt="">
	</div>
	<div class="modal-footer">
		<button type="button" class="imgNavigator btn pull-left" value="-1" title="Предыдущее фото"><i class="icon-chevron-left"></i></button>
		<button type="button" class="imgNavigator btn pull-left" value="1" title="Следующее фото"><i class="icon-chevron-right"></i></button>
		<button type="button" class="btn btn-primary" data-dismiss="modal" aria-hidden="true">Закрыть</button>
	</div>
</div>

<div class="modal hide" id="frameActionM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4>Действия c новым фреймом</h4>
	</div>
	<div class="modal-body" id="frameActionSelector">
		<label><input type="text" id="newFrameName" placeholder="Новое имя"> Имя нового фрейма</label>
		Нужно:<br>
		<label><input type="radio" name="frameAction" class="frameAction" value="0"> Создать новый пустой фрейм</label>
		<label><input type="radio" name="frameAction" class="frameAction" value="1"> Создать фрейм, скопировав объекты из 
		<select id="frameSelectorList"></select></label>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn" id="cancelNewFrame" data-dismiss="modal" aria-hidden="true">Отмена</button>
		<button type="button" class="btn btn-primary" id="submitFrameAction">Готово</button>
	</div>
</div>

<div class="modal hide" id="newMapM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4>Новая карта</h4>
	</div>
	<div class="modal-body">
		<label><input type="text" id="newMapName" placeholder="Новое имя"> Название карты</label>
		Свойства:<br>
		<label><input type="text" id="firstFrameName" placeholder="Новое имя"> Имя первого фрейма</label>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn" data-dismiss="modal" aria-hidden="true">Отмена</button>
		<button type="button" class="btn btn-primary" id="mapReset">Готово</button>
	</div>
</div>

<div class="modal hide" id="mapPropertiesM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4>Свойства карты</h4>
	</div>
	<div class="modal-body">
		<label><input type="text" id="mapNameProperty" placeholder="Новое имя"> Название карты</label>
		<hr>
		<label><input type="text" id="frameName" placeholder="Новое имя"> Имя фрейма</label>
		<ul id="frameList" class="sortable"></ul>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn" data-dismiss="modal" aria-hidden="true">Отмена</button>
		<button type="button" class="btn btn-primary" id="mapRearrange">Готово</button>
	</div>
</div>

<div class="modal hide" id="objectManagerM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4>Менеджер объектов</h4>
	</div>
	<div class="modal-body" style="height:280px;overflow:auto;">
		<table class="table table-condensed table-bordered">
		<tr>
			<th style="width:50px;">Фрейм</td>
			<th>Название</td>
			<th style="width:90px;">Действия</td>
		</tr>
		<tbody id="objectManagerTbody"></tbody>
		</table>
	</div>
	<div class="modal-footer">
		<button type="button" class="btn" data-dismiss="modal" aria-hidden="true">Отмена</button>
		<button type="button" class="btn btn-primary" data-dismiss="modal" aria-hidden="true">Готово</button>
	</div>
</div>

<div class="well hide container-fluid" id="mapLinkContainer" style="height:28px;padding:5px;position:absolute;top:45%; left:30%; width:580px;">
	<input type="text" name="mapLink" id="mapLink" value="" style="width:480px;" class="pull-left">
	<button type="button" class="btn btn-small btn-primary pull-right" id="linkClose" style="margin-top:2px;">Закрыть</button>
</div>

<div style="display:none;">
	<input type="hidden" name="maphash"      id="maphash"      value="<?=$maphash;?>">
	<input type="hidden" name="current_zoom" id="current_zoom" value="<?=$zoom;?>">
	<input type="hidden" name="current_type" id="current_type" value="<?=$maptype;?>">
	<input type="hidden" name="map_center"   id="map_center"   value="<?=$maps_center;?>">
	<input type="hidden" name="current_obj_type" id="current_obj_type" value="1">
	<input type="hidden" name="gCounter"     id="gCounter"     value="<?=$gcounter;?>">
	<input type="hidden" name="location_id"  id="location_id"  value="">
</div>
<!-- API 2.0 -->
<?=$this->config->item("api_set");?>
<!-- EOT API 2.0 -->
<!-- latest version available at WWW.KORZHEVDP.COM -->
</body>
</html>