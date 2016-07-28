<!-- вырезать отсюда / cut from here -->
<meta http-equiv="content-type" content="text/html; charset=utf-8">
<style type="text/css">
	body {
		margin: 0; padding: 0;
	}
	#YMapsID {
		width:100%;height:100%;outline:1px solid #FFFFFF;margin:0px;
	}
	#l_photo {
		margin-right: 4px; margin-bottom: 4px; width: 128px; height: 128px; float: left;
	}
	.ymaps_balloon{
		color: #000000; width: 350px; padding-bottom:15px; font-size: 8pt; font-family: Tahoma; line-height:14px; 
	}
	.ymaps_balloon .propertyContainer {
		clear: right; margin-top: 4px;
	}
	.ymaps_balloon .property {
		margin-right:10px; font-weight:bold; float:left;
	}
	.ymaps_balloon .link {
		clear: both; margin-top: 6px;
	}
	#l_photo{
		cursor: pointer;
	}
	#viewerM {
		width:630px !important;
	}
	#viewerM .modal-body{
		display: table-cell; text-align:center; vertical-align:middle; width:600px; height:500px; 
	}
	#viewerM .modal-body img {
		display: inline;
	}
	.controls {
		padding: 3px; border: 2px solid rgb(255, 255, 255); left: 360px; top: 14px; width: 400px; height: 26px; color: rgb(214, 214, 214); vertical-align: middle; position: absolute; z-index: 99; background-color: #c8c8c8;
	}
	#uiFrameName {
		width: 280px; overflow:hidden; color: black; font-size: 18px; font-weight:bold; padding-top:2px;
	}
	#frameNum {
		width:25px;height:25px;margin-bottom:1px;padding:0;
	}

</style>
<link href="<?=$this->config->item('api');?>/bootstrap/css/bootstrap.css" rel="stylesheet" />
<script type="text/javascript" src="<?=$this->config->item('api');?>/jscript/jquery.js"></script>
<script type="text/javascript" src="<?=$this->config->item('api');?>/bootstrap/js/bootstrap.min.js"></script>
<script type="text/javascript" src="http://api-maps.yandex.ru/2.0/?coordorder=longlat&load=package.full&lang=ru-RU" ></script>
<script type="text/javascript" src="<?=$this->config->item('base_url').$this->config->item('userStylesPath');?>"></script>

<div id="YMapsID"></div>

<div class="controls">
	<div class="pull-left" id="uiFrameName">
		
	</div>
	<div class="pull-right">
		<a class="btn frameSwitcher" id="prevFrame" ref="-1" href="#"><i class="icon-chevron-left"></i></a>
		<input type="text" id="frameNum" value="1" readonly="readonly" maxlength=3>
		<a class="btn frameSwitcher" id="nextFrame" ref="1" href="#"><i class="icon-chevron-right"></i></a>
	</div>
</div>

<div class="modal hide" id="viewerM">
	<div class="modal-header">
		<button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
		<h4>Фотографии объекта</h4>
	</div>
	<div class="modal-body">
		<img src="<?=$this->config->item("base_url");?>images/nophoto.jpg" id="locImg" border="0" alt="">
	</div>
	<div class="modal-footer">
		<button type="button" class="imgNavigator btn pull-left" value="-1" title="Предыдущее фото"><i class="icon-chevron-left"></i></button>
		<button type="button" class="imgNavigator btn pull-left" value="1" title="Следующее фото"><i class="icon-chevron-right"></i></button>
		<button type="button" class="btn btn-primary" data-dismiss="modal" aria-hidden="true">Закрыть</button>
	</div>
</div>


<script type="text/javascript">
	var api_url = '<?=$this->config->item("api");?>',
		frame   = 1; 
	function display_locations() {
		var objects = {
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
				behaviors : ["scrollZoom", "drag", "dblClickZoom", "multiTouch"]
			},
			{
				maxZoom              : 19,
				projection           : ymaps.projection.sphericalMercator,
				suppressMapOpenBlock : true,
				yandexMapAutoSwitch  : false
			}),
			ms             = {},
			aObjects       = new ymaps.GeoObjectArray(),
			genericBalloon = ymaps.templateLayoutFactory.createClass(
				'<div class="ymaps_balloon">' +
					'<div id="l_photo" data-toggle="modal" picref="$[properties.ttl|0]">' +
					'<img src="$[properties.img128|' + api_url + '/images/nophoto.jpg]">' +
					'</div>' +
				'<div class="propertyContainer"><div class="property">Название:</div>&nbsp;$[properties.name|без имени]</div>' +
				'<div class="propertyContainer"><div class="property">Адрес:</div>&nbsp;$[properties.addr|нет]</div>' +
				'<div class="propertyContainer"><div class="property">Описание:</div>&nbsp;$[properties.desc|без описания]</div>' +
				'[if properties.link]<div class="link"><a class="btn btn-info btn-small btn-block" href="$[properties.link|#]" target="_blank">Подробности здесь</a></div>[endif]</div>'
			);
		map.controls.add('mapTools').add('zoomControl');
		ymaps.layout.storage.add('generic#balloonLayout', genericBalloon);
		aObjects.options.set({
			balloonContentBodyLayout: 'generic#balloonLayout',
			balloonWidth: 370// Максимальная ширина балуна в пикселах
		});
		for (a in userstyles){
			ymaps.option.presetStorage.add(a, userstyles[a]);
		}
		
		map.geoObjects.add(aObjects);

		map.events.add("balloonopen", function(){
			$("#l_photo").unbind().click(function() {
				var ref = $(this).attr("picref"),
					imageSet = objects[ref].img,
					i = 0;
				function coalesceLocImages(filename){
					if (filename.length) {
						$("#locImg").attr("src", '<?=$this->config->item("base_url");?>storage/600/' + filename);
						return true;
					}
					$("#locImg").attr("src", "http://api.arhcity.ru/images/nophoto.jpg");
				}
				coalesceLocImages(imageSet[i]);
				if (imageSet.length) {
					i = 1;
					$(".imgNavigator").click(function() {
						if (i >= imageSet.length) {
							i = 0;
						}
						if (i < 0) {
							i = imageSet.length - 1;
						}
						coalesceLocImages(imageSet[i]);
						i += parseInt($(this).val(), 10);
					});
				}
				$("#viewerM").modal("show");
			});
		});

		for ( a in objects ) {
			if (objects.hasOwnProperty(a)) {
				ms[a] = new ymaps.GeoObjectArray();
			}
		}

		for (a in objects ) {
			if (objects.hasOwnProperty(a)) {
				cFrame = objects[a].objects
				for ( b in cFrame ) {
					item = cFrame[b];
					ms[a].add(fx[item.type](item));
				}
			}
		}

		function switchFrame(referer) {
			hideFrame(frame);
			frame += parseInt(referer, 10);
			frame = (frame > 1) ? frame : 1;
			if (ms[frame] === undefined) {
				frame -= 1;
				showFrame(frame);
				return false;
			}
			showFrame(frame);
		}

		function hideFrame(frame) {
			if (ms[frame] === undefined) {
				return false;
			}
			ms[frame].removeAll();
			while (aObjects.get(0)) {
				ms[frame].add(aObjects.get(0));
			}
		}

		function showFrame(frame) {
			/*
			функция отображения фрейма
			*/
			if ( ms[frame] !== undefined) {
				while (ms[frame].get(0)) {
					aObjects.add(ms[frame].get(0));
				}
			}
			//console.log(frame, usermap[frame].name, aObjects.get(0).properties.get('attr'))
			if ( objects !== undefined && objects[frame] !== undefined && objects[frame].name !== undefined ) {
				$("#uiFrameName").empty().html(objects[frame].name);
			}
			$("#frameActionM").modal('hide');
			$("#frameNum").val(frame);
		}

		$(".frameSwitcher").click(function() {
			switchFrame($(this).attr("ref"));
		});

		showFrame(frame);
	}

	$(".modal").modal("hide");
	ymaps.ready(display_locations);
</script>
<!-- до сюда / till here -->