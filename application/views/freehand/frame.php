<!-- вырезать отсюда / cut from here -->
<style type="text/css">
.ymaps_balloon{
	color: #000000;
	width: 275px;
	padding-bottom:15px;
	font-size: 8pt;
	font-family: Tahoma;
	line-height:14px;
}
</style>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="http://api-maps.yandex.ru/2.0/?coordorder=longlat&load=package.full&lang=ru-RU" type="text/javascript"></script>
<script type="text/javascript" src="<?=$this->config->item("api");?>/jscript/map_styles2.js"></script>

<div id="YMapsID" style="width:100%;height:98%;border:1px solid #FFFFFF;margin:0px;"></div>
<script type="text/javascript">
	function display_locations() {
		map = new ymaps.Map("YMapsID", {
			center    : [<?=$maplon;?>,<?=$maplat;?>],
			zoom      : <?=$mapzoom;?>,
			type      : '<?=$maptype;?>',
			behaviors : ["scrollZoom", "drag", "dblClickZoom"]
		},
			{
				maxZoom              : 19,
				projection           : ymaps.projection.sphericalMercator,
				suppressMapOpenBlock : true,
				yandexMapAutoSwitch  : false
			});
		map.controls.add('mapTools').add('zoomControl');
		for (a in userstyles){
			ymaps.option.presetStorage.add(a, userstyles[a]);
		}
		ms = new ymaps.GeoObjectArray();
		var genericBalloon = ymaps.templateLayoutFactory.createClass(
			'<div class="ymaps_balloon">' +
			'<b>Название:</b> $[properties.name|без имени]<br><b>Адрес:</b> $[properties.address|нет]<br>' +
			'<b>Описание:</b> $[properties.description|без описания]' +
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
	ymaps.ready(display_locations);
</script>
<!-- до сюда / till here -->