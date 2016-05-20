<!-- вырезать отсюда / cut from here -->
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<style type="text/css">
	#YMapsID {
		width:100%;height:98%;border:1px solid #FFFFFF;margin:0px;
	}
	#l_photo {
		margin-right: 4px; margin-bottom: 4px; width: 128px; height: 128px; float: left;
	}
	.ymaps_balloon{
		color: #000000; width:370px;padding-bottom:15px; font-size: 9pt; font-family: Tahoma; line-height:14px;
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

<script type="text/javascript" src="http://api-maps.yandex.ru/2.0/?coordorder=longlat&load=package.full&lang=ru-RU" ></script>
<script type="text/javascript" src="<?=$this->config->item("base_url");?>scripts/styles"></script>

<div id="YMapsID"></div>
<script type="text/javascript">
	var api_url = '<?=$this->config->item("api");?>';
	function display_locations() {
		var	objects = {
				<?=$mapobjects;?>
	
		},
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
			},
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
			}),
			ms = new ymaps.GeoObjectArray(),
			genericBalloon = ymaps.templateLayoutFactory.createClass(
				'<div class="ymaps_balloon">' +
					'<div id="l_photo" data-toggle="modal" picref="$[properties.ttl|0]">' +
					'$[properties.img128|<img src="' + api_url + '/images/nophoto.jpg">]' +
					'</div>' +
				'<div class="propertyContainer"><div class="property">Название:</div>&nbsp;$[properties.name|без имени]</div>' +
				'<div class="propertyContainer"><div class="property">Адрес:</div>&nbsp;$[properties.addr|нет]</div>' +
				'<div class="propertyContainer"><div class="property">Описание:</div>&nbsp;$[properties.desc|без описания]</div>' +
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