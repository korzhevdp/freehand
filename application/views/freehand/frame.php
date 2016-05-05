<!-- вырезать отсюда / cut from here -->
<style type="text/css">
#l_photo {
	margin-right: 4px; margin-bottom: 4px; width: 128px; height: 128px; float: left;
}
.ymaps_balloon{
	color: #000000; width:370px;padding-bottom:15px; font-size: 8pt; font-family: Tahoma; line-height:14px;
}
.ymaps_balloon div.propertyContainer {
	clear: right; margin-top: 4px;
}

.ymaps_balloon div.property {
	margin-right:10px; font-weight:bold; float:left;
}

.ymaps_balloon div.link {
	clear: both; margin-top: 6px;
}

.ymaps_balloon div.link {
	clear: both; margin-top: 6px;
}
</style>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8">
<script src="http://api-maps.yandex.ru/2.0/?coordorder=longlat&load=package.full&lang=ru-RU" type="text/javascript"></script>
<script type="text/javascript" src="<?=$this->config->item("api");?>/jscript/map_styles2.js"></script>

<div id="YMapsID" style="width:100%;height:98%;border:1px solid #FFFFFF;margin:0px;"></div>
<script type="text/javascript">
	var api_url = '<?=$this->config->item("api");?>';
	function display_locations() {
		var map = new ymaps.Map("YMapsID", {
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
				}),
			ms = new ymaps.GeoObjectArray(),
			genericBalloon = ymaps.templateLayoutFactory.createClass(
				'<div class="ymaps_balloon">' +
					'<div id="l_photo" data-toggle="modal" picref="$[properties.ttl|0]">' +
					'$[properties.img128|<img src="' + api_url + '/images/nophoto.jpg">]' +
					'</div>' +
				'<div class="propertyContainer"><div class="property">Название:</div>&nbsp;$[properties.name|без имени]</div>' +
				'<div class="propertyContainer"><div class="property">Адрес:</div>&nbsp;$[properties.address|нет]</div>' +
				'<div class="propertyContainer"><div class="property">Описание:</div>&nbsp;$[properties.description|без описания]</div>' +
				'<div class="link"><a href="$[properties.link|#]" target="_blank">Подробности здесь</a></div></div>'
			);
		map.controls.add('mapTools').add('zoomControl');
		ymaps.layout.storage.add('generic#balloonLayout', genericBalloon);
		ms.options.set({
			balloonContentBodyLayout: 'generic#balloonLayout',
			balloonWidth: 370// Максимальная ширина балуна в пикселах
		});
		for (a in userstyles){
			ymaps.option.presetStorage.add(a, userstyles[a]);
		}
		<?=$mapobjects;?>
		map.geoObjects.add(ms);
	}
	ymaps.ready(display_locations);
</script>
<!-- до сюда / till here -->