<!DOCTYPE HTML>
<html>
<head>
	<title>Minigis.NET - выгрузка карты</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<body>

<!-- вырезать отсюда / cut from here -->
	<STYLE TYPE="text/css">
	.ymaps_balloon{
		color: #000000;
		width: 275px;
		padding-bottom:15px;
		font-size: 8pt;
		font-family: Tahoma;
		line-height:14px;
	}
	#YMapsID{
		width:100%;
		height:600px;
		border:1px solid #FFFFFF;
	}
	</STYLE>


	<div id="YMapsID"></div>

	<script src="http://api-maps.yandex.ru/2.0/?coordorder=longlat&amp;load=package.full&amp;lang=ru-RU" type="text/javascript"></script>
	<script type="text/javascript" src="<?=$this->config->item('api');?>/jscript/map_styles2.js"></script>
	<script type="text/javascript">
		ymaps.ready(display_locations);
		function display_locations(){
			map = new ymaps.Map("YMapsID", {
				center: [ <?=$maplon;?>, <?=$maplat;?> ],
				zoom: <?=$mapzoom;?>,
				type: '<?=$maptype;?>',
				behaviors: ["scrollZoom", "drag", "dblClickZoom"]
			});
			map.controls.add('mapTools');
			for (a in userstyles){
				ymaps.option.presetStorage.add(a,userstyles[a]);
			}
			ms = new ymaps.GeoObjectArray();
			var genericBalloon = ymaps.templateLayoutFactory.createClass(
				'<div class="ymaps_balloon">' +
				'<b>Название:</b> $[properties.name|без имени]<br>' +
				'<b>Адрес:</b> $[properties.address|нет]<br>' +
				'<b>Описание</b> $[properties.description|без описания]<br>' +
				'<a href="$[properties.link|#]">Подробнее здесь</a><br>' +
				'</div></div>'
			);
			ymaps.layout.storage.add('generic#balloonLayout', genericBalloon);
			ms.options.set({
				balloonContentBodyLayout: 'generic#balloonLayout',
				balloonMaxWidth: 300// Максимальная ширина балуна в пикселах
			});
			<?=$mapobjects;?>
			map.geoObjects.add(ms);
		}
	</script>
<!-- до сюда / till here -->

</body>
</html>