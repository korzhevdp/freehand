<!DOCTYPE HTML>
<html>
<head>
	<title>Minigis.NET - выгрузка карты</title>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8">
</head>

<body>

<!-- вырезать отсюда / cut from here -->
	<style type="text/css">
		.ymaps_balloon{
			color: #000000; width: 275px; padding-bottom:15px; font-size: 8pt; font-family: Tahoma; line-height:14px; 
		}
		#YMapsID {
			width: 100%; height: 600px; border : 1px solid #ffffff;
		}
		a {
			color: #A6A6A6; font-size: 12pt;
		}
	</style>

	<div id="YMapsID"></div>

	<script src="http://api-maps.yandex.ru/2.0/?coordorder=longlat&amp;load=package.full&amp;lang=ru-RU" type="text/javascript"></script>
	<script type="text/javascript" src="<?=$this->config->item("base_url");?>scripts/styles"></script>
	<script type="text/javascript">
		function display_locations(){
			var a,
				item,
				genericBalloon = ymaps.templateLayoutFactory.createClass(
					'<div class="ymaps_balloon">' +
					'<b>Название:</b> $[properties.name|без имени]<br>' +
					'<b>Адрес:</b> $[properties.addr|нет]<br>' +
					'<b>Описание</b> $[properties.desc|без описания]<hr>' +
					'<a href="$[properties.link|#]">Подробнее здесь</a><br>' +
					'</div>'
				),
				objects = [
					<?=$mapobjects;?>
				
				],
				fx  = {
					1: function(item) {
						var coords     = item.coords.split(",");
							geometry   = new ymaps.geometry.Point([ parseFloat(coords[0]), parseFloat(coords[1]) ]);
							options    = ymaps.option.presetStorage.get(item.attr),
							properties = item;
							
						return new ymaps.Placemark( geometry, properties, options );
					},
					2: function(item) {
						var geometry   = ymaps.geometry.LineString.fromEncodedCoordinates(item.coords),
							options    = ymaps.option.presetStorage.get(item.attr),
							properties = item;
						return new ymaps.Polyline( geometry, properties, options );
					},
					3: function(item) {
						var geometry   = ymaps.geometry.Polygon.fromEncodedCoordinates(item.coords),
							options    = ymaps.option.presetStorage.get(item.attr),
							properties = item;
						return new ymaps.Polygon( geometry, properties, options );
					},
					4: function(item) {
						var coords = item.coords.split(","),
							geometry   = new ymaps.geometry.Circle([parseFloat(coords[0]), parseFloat(coords[1])], parseFloat(coords[2])),
							options    = ymaps.option.presetStorage.get(item.attr),
							properties = item;
						return new ymaps.Circle( geometry, properties, options );
					}
				}
				map = new ymaps.Map("YMapsID", {
					center    : [ <?=$maplon;?>, <?=$maplat;?> ],
					zoom      : <?=$mapzoom;?>,
					type      : '<?=$maptype;?>',
					behaviors : ["scrollZoom", "drag", "dblClickZoom", "multiTouch"]
				},
				{
					maxZoom              : 19,
					projection           : ymaps.projection.sphericalMercator,
					suppressMapOpenBlock : true,
					yandexMapAutoSwitch  : false
				});
			map.controls.add('mapTools');

			for (a in userstyles){
				ymaps.option.presetStorage.add(a,userstyles[a]);
			}

			ymaps.layout.storage.add('generic#balloonLayout', genericBalloon);
			ms = new ymaps.GeoObjectCollection();
			ms.options.set({
				balloonContentBodyLayout : 'generic#balloonLayout',
				balloonMaxWidth          : 300
			});
			map.geoObjects.add(ms);

			for (a in objects ) {
				if (objects.hasOwnProperty(a)) {
					item = objects[a];
					ms.add(fx[item.type](item));
				}
			}
		}
		ymaps.ready(display_locations);
	</script>
<!-- до сюда / till here -->

</body>
</html>