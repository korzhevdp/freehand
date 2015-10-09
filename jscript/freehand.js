/* jshint -W100 */
/* jshint undef: true, unused: true */
/* globals ymaps, confirm, style_src, usermap, style_paths, yandex_styles, yandex_markers, style_circles, style_polygons, styleAddToStorage */
	'use strict'
var userstyles,
	map,
	a_objects,
	e_objects,
	counter      = 0,
	api_url       = 'http://api.korzhevdp.com',
	controller    = '/freehand',
	mp = {},
	clipboard     = { name: '', description: '', address: '', preset: '', gtype: "Point" },
	gIcons = {
		"Point"      : 'marker.png',
		"LineString" : 'layer-shape-polyline.png',
		"Polygon"    : 'layer-shape-polygon.png',
		"Circle"     : 'layer-shape-ellipse.png'
	},
	geoType2IntId = {
		"Point"			: 1,
		"LineString"	: 2,
		"Polygon"		: 3,
		"Circle"		: 4,
		"Rectangle"		: 5
	},
	intId2GeoType = {
		1 : "Point",
		2 : "LineString",
		3 : "Polygon",
		4 : "Circle",
		5 : "Rectangle"
	},
	mframes = [],
	isCenterFixed  = 0;

ymaps.ready(setup_environment);

function setup_environment() {
	styleAddToStorage(userstyles);
	list_marker_styles();
	list_route_styles();
	list_polygon_styles();
	list_circle_styles();
	display_locations();
}
//######################################### процессор карты #########################################################
function display_locations() {
	var a,
		cursor,
		object,
		layerTypes,
		dX = [],
		typeSelector,
		searchControl,
		viewPort,
		valtz,
		coords,
		forIFrame    = 0,
		object_gid   = $("#gCounter").val(),
		current_type = $("#current_type").val(),
		map_center   = $("#map_center").val(),
		current      = (current !== undefined)     ? current : $('#location_id').val(),
		lon          = (isNaN(ymaps.geolocation.longitude)) ? parseFloat(map_center.toString().split(",")[0]) : ymaps.geolocation.longitude,
		lat          = (isNaN(ymaps.geolocation.latitude))  ? parseFloat(map_center.toString().split(",")[1]) : ymaps.geolocation.latitude,
		current_zoom = ($("#current_zoom").val().length)    ? $("#current_zoom").val() : 15,
		tileServerID = parseInt(Math.random() * (4-1) + 1).toString(),
		tileServerLit= { "0": "a","1": "b","2": "c","3": "d","4": "e","5": "f" },
		genericBalloon = ymaps.templateLayoutFactory.createClass(
			'<div class="ymaps_balloon row-fluid">' +
			'<div class="gallery span2" id="l_photo" data-toggle="modal" picref=$[properties.ttl|0] href="#modal_pics">' +
			'<img src="' + api_url + '/images/$[properties.img|nophoto.gif]" style="margin:3px;" ALT="мини" id="sm_src_pic">' +
			'</div>' +
			'<span style="margin-right:10px;font-weight:bold;">Название:</span> $[properties.name|без имени]<br>' +
			'<span style="margin-right:30px;font-weight:bold;">Адрес:</span> $[properties.address|нет]<br>' +
			'<span style="margin-right:10px;font-weight:bold;">Описание:</span> $[properties.description|без описания]<br>' +
			'<div><a href="$[properties.link|#]" style="margin:3px;margin-top:16px;" class="btn btn-mini btn-block" target="_blank">Подробности здесь</a></div>' +
			'<div class="pull-right" style="margin-top:20px;">' +
			'<button type="button" class="btn btn-mini btn-primary sw-edit" ttl="$[properties.ttl|0]" style="margin-right:10px;">Редактировать </button>' +
			'<button type="button" class="btn btn-mini btn-info balloonClose" style="margin-right:10px;">Закрыть</button>' +
			'</div></div>'
			),
		iframeBalloon = ymaps.templateLayoutFactory.createClass(
			'<div class="ymaps_balloon_iframed">' +
			'<iframe src="$[properties.link|]" width="400" height="400" style="border:none;margin:0;padding:0;"></iframe>' +
			'<div><a href="$[properties.link|#]" style="margin:3px;margin-top:16px;" class="btn btn-mini btn-block" target="_blank">Подробности здесь</a></div>' +
			'<div class="pull-right" style="margin-top:20px;">' +
			'<button type="button" class="btn btn-mini btn-primary sw-edit" ttl="$[properties.ttl|0]" style="margin-right:10px;">Редактировать </button>' +
			'<button type="button" class="btn btn-mini btn-info balloonClose" style="margin-right:10px;">Закрыть</button>' +
			'</div></div>'
			),
		editBalloon = ymaps.templateLayoutFactory.createClass(
			'<div class="ymaps_balloonX row-fluid">' +
			'<span class="span3" style="margin-left:0px;">Название:</span><input type="text" id="bal_name" class="span9 input-mini" value="$[properties.name|без имени]">'+
			'<span class="span3" style="margin-left:0px;">Адрес:</span><input type="text" id="bal_addr" class="span9 input-mini" value="$[properties.address|нет]">' +
			'<span class="span3" style="margin-left:0px;">Ссылка:</span><input type="text" id="bal_link" class="input-mini span9" placeholder="Ссылка на web-страницу или изображение" value="$[properties.link|#]">' +
			'<span class="span3" style="margin-left:0px;">Описание:</span><textarea id="bal_desc" rows="6" cols="6" class="span12">$[properties.description|нет]</textarea>' +
			'<!-- <a href="#" id="editX" class="btn btn-small btn-block editX" ttl="$[properties.ttl|0]">Расширенное редактирование</a> -->' +
			'<div class="pull-right">' +
			'<button type="button" class="btn btn-mini btn-primary sw-finish" ttl="$[properties.ttl|0]" style="margin-right:10px;">Готово</button>' +
			'<button type="button" class="btn btn-mini btn-danger sw-del" ttl="$[properties.ttl|0]" style="margin-right:10px;">Удалить</button>' +
			'<button type="button" class="btn btn-mini btn-info balloonClose">Закрыть</button>' +
			'</div></div>'
		);

		//определение механизма пересчёта стандартной сетки тайлов в сетку тайлов Яндекс-карт (TMS)
		//api_url = (typeof $("#api_url") != 'undefined' && $("#api_url").val().length) ? $("#api_url").val() : "http://api.korzhevdp.com",
	for ( a = 0; a < 21; a++) { 
		dX[a] = Math.pow(2, a) - 1;
	}
	layerTypes   = {
		0: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[0].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/nm/base/",
			label : "base#arch",
			name  : "Нарьян-Мар 1943 HD",
			layers: ['yandex#satellite', "base#arch"]
		},
		1: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[1].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/1990/",
			label : "base2#arch",
			name  : "Архангельск. План 1998 года",
			layers: ['yandex#satellite', "base2#arch"]
		},
		2: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[2].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/arch1940/base/",
			label : "base3#arch",
			name  : "Архангельск. 1941-43 гг. Стандартное разрешение",
			layers: ['yandex#satellite', "base3#arch"]
		},
		3: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[3].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/arch1940/centerhr/",
			label : "base4#arch",
			name  : "Архангельск. 1941-43 гг. Центр. Высокое разрешение",
			layers: ['yandex#satellite', "base3#arch", "base4#arch"]
		},
		4: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[4].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/arch1940/farnorth/",
			label : "base5#arch",
			name  : "Архангельск. 1941-43 гг. Север, фрагменты. Высокое разрешение",
			layers: ['yandex#satellite', "base3#arch", "base5#arch"]
		},
		5: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[5].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/molotowsk/Molotowsk041/",
			label : "base#molot",
			name  : "Молотовск и окрестности 25.04.1943 г.",
			layers: ['yandex#satellite', "base#molot"]
		},
		6: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[6].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/molotowsk/Molotowsk040/",
			label : "base#molot2",
			name  : "Молотовск, центр города 25.04.1943 г.",
			layers: ['yandex#satellite', "base#molot", "base#molot2"]
		},
		7: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[7].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/molotowsk/Molotowsk042/",
			label : "base#molot3",
			name  : "Молотовск. Завод. 8.07.1943 г.",
			layers: ['yandex#satellite', "base#molot", "base#molot3"]
		},
		8: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[8].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "Molotowsk044/",
			label : "base#molot4",
			name  : "Молотовск. Завод. Ягры. 15.08.1943 г.",
			layers: ['yandex#satellite', "base#molot", "base#molot4"]
		},
		9: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[9].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png';}, {tileTransparent: 1, zIndex:1000});},
			folder: "http://luft.korzhevdp.com/maps/molotowsk/Molotowsk049/",
			label : "base#molot5",
			name  : "Молотовск. Завод. 15.08.1943 г.",
			layers: ['yandex#satellite', "base#molot5"]
		},
		10: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return "http://mt" + tileServerID + ".google.com/vt/lyrs=m&hl=ru&x=" + tile[0] + "&y=" + tile[1] + "&z=" + zoom + "&s=Galileo";}, {tileTransparent: 1, zIndex:1000});},
			folder: "",
			label : "map#google",
			name  : "Гуглотест",
			layers: ["map#google"]
		},
		12: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return "http://mt" + tileServerID + ".google.com/vt/lyrs=s&hl=ru&x=" + tile[0] + "&y=" + tile[1] + "&z=" + zoom + "&s=Galileo";}, {tileTransparent: 1, zIndex:1000});},
			folder: "",
			label : "satellite#google",
			name  : "Гуглотест Спутник",
			layers: ["satellite#google"]
		},
		11: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return "http://" + tileServerLit[tileServerID] + ".tile.openstreetmap.org/" + zoom + "/" + tile[0] + "/" + tile[1] + ".png";}, {tileTransparent: 1, zIndex:1000});},
			folder: "",
			label : "map#osm",
			name  : "OSMтест",
			layers: ["map#osm"]
		}
	};
	a_objects    = new ymaps.GeoObjectArray();
	e_objects    = new ymaps.GeoObjectArray();
	typeSelector = new ymaps.control.TypeSelector();
	//ex_objects    = new ymaps.GeoObjectArray(), //--B2

	// создаём слои наложения для карты
	for (a in layerTypes) {
		if (layerTypes.hasOwnProperty(a)) {
			ymaps.layer.storage.add(layerTypes[a].label, layerTypes[a].func);
			ymaps.mapType.storage.add(layerTypes[a].label, new ymaps.MapType(
				layerTypes[a].name, layerTypes[a].layers
			));
			typeSelector.addMapType(layerTypes[a].label, a);
		}
	}

	map = new ymaps.Map("YMapsID", {
		center: (map_center.length) ? [ parseFloat(map_center.split(",")[1]), parseFloat(map_center.split(",")[0]) ] : [lon, lat],
		zoom: current_zoom,
		type: current_type,
		behaviors: ["scrollZoom", "drag", "dblClickZoom"]
	},
	{
		projection: ymaps.projection.sphericalMercator
	});

	/* назначаем курсор-стрелку для улучшенного позиционирования */
	cursor = map.cursors.push('crosshair', 'arrow');
	cursor.setKey('arrow');

	/* ViewPort data fields */
	viewPort = {
		frame  : 1,
		c_c    : [ map.getCenter()[0].toFixed(8), map.getCenter()[1].toFixed(8) ],
		zoom   : 13,
		c_Type : 'yandex#satellite'
	};

	$("#vp_frame").val(1);
	$("#vp_lon").val(map.getCenter()[0].toFixed(8));
	$("#vp_lat").val(map.getCenter()[1].toFixed(8));
	$("#current_obj_type").val("Point");


	// ##### настройка представления карты #####
	searchControl = new ymaps.control.SearchControl({ provider: 'yandex#publicMap'});

	map.controls.add('zoomControl').add(typeSelector).add('mapTools').add(searchControl);
	typeSelector.removeMapType('yandex#publicMapHybrid');
	typeSelector.removeMapType('yandex#hybrid');
	typeSelector.removeMapType('yandex#publicMap');
	//$(".ymaps-b-form-input__input").empty().attr("placeholder", ymaps.geolocation.city);

	ymaps.layout.storage.add('generic#balloonLayout', genericBalloon);
	ymaps.layout.storage.add('editing#balloonLayout', editBalloon);
	ymaps.layout.storage.add('iframe#balloonLayout' , iframeBalloon);
	//ymaps.layout.storage.add('editingx#balloonLayout', editxBalloon); //--B2

	a_objects.options.set({
		balloonContentBodyLayout: (forIFrame) ? 'iframe#balloonLayout' : 'generic#balloonLayout',
		balloonMaxWidth:  800,
		balloonMaxHeight: 800
	});

	e_objects.options.set({
		balloonContentBodyLayout: 'editing#balloonLayout',
		balloonWidth: 800
	});
	// ##### события #####
	// карта

	map.events.add('balloonopen', function () {
		$('#upload_location').val($('#l_photo').attr('picref'));
		action_listeners_add();
	});

	/*
	map.events.add('balloonclose', function () {
		//carousel_destroy();
	});
	*/

	map.events.add('boundschange', function (data) {
		if (!isCenterFixed) {
			$("#vp_lon").val(data.get('newCenter')[0].toFixed(8)); // сохраняем в поле новое значение центра карты
			$("#vp_lat").val(data.get('newCenter')[1].toFixed(8)); // сохраняем в поле новое значение центра карты
			$("#map_center").val(data.get('newCenter').join(",")); // сохраняем в поле новое значение центра карты
			$("#current_zoom").val(data.get('newZoom')); // сохраняем в поле новое значение масштаба карты
			sendMap();
		}
	});

	map.events.add('typechange', function () {
		$("#current_type").val(map.getType()); // сохраняем в поле новое значение типа карты
		sendMap();
	});

	map.events.add('click', function (click) {
		draw_object(click);
	});

	map.events.add('contextmenu', function (e) {
		coords = e.get('coordPosition');
		ymaps.geocode(coords,{kind: ['house']}).then(function (res) {
			var names = [];
			res.geoObjects.each(function (obj) {
				names.push(obj.properties.get('name'));
			});
			valtz = (names[0] !== undefined) ? [names[0]].join(', ') : "Нет адреса"; [names[0]].join(', ');
			map.balloon.open(coords, {
				contentBody: '<div class="ymaps_balloon row-fluid"><input type="text" value="' + [ coords[0].toPrecision(8), coords[1].toPrecision(8)].join(', ') + '"><br>' + valtz + '</div>'});
		});
	});

	e_objects.events.add('dragend', function(e) {
		var object = e.get('target');
		traceNode(object);
	});

	a_objects.events.add('contextmenu', function(e) {
		if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
			return false;
		}
		object = e.get('target');
		doEdit(object);
		count_objects();
		counter = 1;
	});

	e_objects.events.add('contextmenu', function(e) {
		if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
			return false;
		}
		object = e.get('target');
		doEdit(object);
		count_objects();
		counter = 1;
	});
	// ###### конец описания событий

	map.geoObjects.add(a_objects);
	map.geoObjects.add(e_objects);
	//sendMap();

	//################################## выносные функции

	function apply_preset(src, style) {
		src.options.set( ymaps.option.presetStorage.get(style) );	// назначение стиля в опции.
		src.properties.set( { attr: style } );						// параллельная запись определения в свойства.
		sendObject(src);
	}

	function draw_object(click) {
		var geometry,
			properties,
			object,
			decAddr,
			names		= [],
			valtz		= '',
			coords		= click.get('coordPosition'),
			selectors	= {
				1 : '#m_style',
				2 : '#line_style',
				3 : '#polygon_style',
				4 : '#circle_style',
				5 : ''
			},
			pr_type		= geoType2IntId[ $("#current_obj_type").val() ],
			realStyle	= normalize_style( $(selectors[pr_type]).val(), pr_type ),
			options;
		options		= ymaps.option.presetStorage.get(realStyle);
		properties	= {
			attr        : realStyle,
			frame       : parseInt($('#vp_frame').val()),
			ttl         : ++object_gid,
			name        : "Название",
			img         : "nophoto.gif",
			hintContent : '',
			address     : '',
			contact     : '',
			description : ''
		};

		if ( mp !== undefined && mp.id !== undefined && mp.id === 'void') {
			console.log("Рисование запрещено");
			return false;
		}

		if (pr_type === 0) {
			console.log("Ошибка в декодировании типа. 0 не является допустимым типом");
			return false;
		}

		if (counter) {
			if (!confirm("На карте присутствуют редактируемые объекты.\nЗавершить их редактирование и создать новый объект?")) {
				return false;
			}
			doFinishAll();
		}

		ymaps.geocode(coords, {kind: ['house']})
		.then(function(res) {
			res.geoObjects.each(function (obj) {
				names.push(obj.properties.get('name'));
			});
			valtz = names[0];
		})
		.then(function() {
			decAddr = (valtz === undefined || ![valtz].join(', ').length) ? "Нет адреса" : [valtz].join(', ');
			properties.description = decAddr;
			properties.hintContent = decAddr;
			properties.address     = decAddr;
		});

		switch (pr_type) {
			case 1 :
				geometry = { type: "Point", coordinates: click.get('coordPosition') };
				object   = new ymaps.Placemark(geometry, properties, options);
				traceNode(object);
			break;
			case 2 :
				geometry = { type: 'LineString', coordinates: [click.get('coordPosition')] };
				object   = new ymaps.Polyline(geometry, properties, options);
				sendObject(object);
			break;
			case 3 :
				geometry = { type: 'Polygon',coordinates: [[click.get('coordPosition')]] };
				object   = new ymaps.Polygon(geometry, properties, options);
				sendObject(object);
			break;
			case 4 :
				geometry = [click.get('coordPosition'),$("#cir_radius").val()];
				object   = new ymaps.Circle(geometry, properties, options);
				traceNode(object);
			break;
		}
		object.properties.set({ preset : realStyle });
		object.options.set({ draggable : 1 });
		e_objects.add(object);
		if (pr_type === 2 || pr_type === 3) {
			object.editor.startDrawing();
		}
		counter++;
		$('#location_id').val(object_gid);
		count_objects();
	}

	function loadmap(name) {
		$.ajax({
			url: controller + "/loadmap",
			type: "POST",
			data: {
				name: name
			},
			dataType: "script",
			success: function() {
				a_objects.removeAll();
				e_objects.removeAll();
				if (usermap.error === undefined) {
					place_freehand_objects(usermap);
				}else{
					console.log(usermap.error);
				}
				if (mp !== undefined) {
					$("#mapSave, #ehashID, #SContainer").css('display', ((mp.id === 'void') ? 'none' : 'block'));
					map.setType(mp.maptype).setZoom(mp.zoom).panTo(mp.c);
				}
				count_objects();
				lock_center();
			},
			error: function(data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}

	function loadSessionData() {
		/*
		загрузка данных сессии
		*/
		$.ajax({
			url: controller + "/get_session",
			dataType: "script",
			type: "POST",
			success: function() {
				place_freehand_objects(usermap);
			},
			error: function(data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}

	function saveAll() {
		/*
		сохранение в базу данных
		*/
		doFinishAll();
		$.ajax({
			url: controller + "/savedb",
			type: "POST",
			dataType: "script",
			success: function() {
				a_objects.removeAll();
				e_objects.removeAll();
				place_freehand_objects(usermap);
				count_objects();
			},
			error: function(data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}

	function sendMap() {
		if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
			return false;
		}
		$.ajax({
			url: controller + "/savemap",
			type: "POST",
			data: {
				maptype: map.getType(),
				center: [ $("#vp_lat").val(), $("#vp_lon").val() ],
				zoom: map.getZoom()
			},
			datatype: "text",
			success: function() {
				console.log("Data sent");
			},
			error: function(data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}

	$("#m_style, #line_style, #polygon_style, #circle_style").change(function() {
		var val = $(this).val();
		e_objects.each(function(item) {
			apply_preset(item, val);
		});
	});

	$("#mapLoader").click(function() {
		loadmap($("#mapName").val());
	});

	$("#mapSave").click(function() {
		saveAll();
	});

	// установка параметров круга
	$(".circlecoord").blur(function() {
		setCircleCoordinates();
	});
	$("#cir_radius").keyup(function() {
		setCircleRadius();
	});
	// последняя функция процессора карты - загрузка карты по id из строки браузера #################################
	if ($("#maphash").val().length === 16) {
		loadmap($("#maphash").val());
		$("#mapName").val($("#maphash").val());
	}else{
		loadSessionData();
	}
}
//######################################### конец процессора карты #####################################################
//######################################################################################################################

function place_freehand_objects(source) {
	var src,
		object,
		geometry,
		options,
		properties,
		b, a,
		frameCounter,
		frm;
	for (b in source) {
		if (source.hasOwnProperty(b)) {
			src = source[b];
			options = ymaps.option.presetStorage.get(normalize_style(src.a, src.p));
			frm = (src.frame === undefined) ? 1 : parseInt(src.frame);
			properties = {
				attr        : src.a,
				description : src.d,
				address     : src.b,
				hintContent : src.n + ' ' + src.d,
				img         : src.img,
				frame       : frm,
				link        : src.l,
				name        : src.n,
				ttl         : b
			};
			if (mframes[frm] === undefined) {
				mframes[frm] = new ymaps.GeoObjectArray();
			}
			if (src.p === 1) {
				geometry = src.c.split(",");
				object   = new ymaps.Placemark(geometry, properties, options);
			}
			if (src.p === 2) {
				geometry = new ymaps.geometry.LineString.fromEncodedCoordinates(src.c);
				object   = new ymaps.Polyline(geometry, properties, options);
			}
			if (src.p === 3) {
				geometry = new ymaps.geometry.Polygon.fromEncodedCoordinates(src.c);
				object   = new ymaps.Polygon(geometry, properties, options);
			}
			if (src.p === 4) {
				geometry = new ymaps.geometry.Circle([parseFloat(src.c.split(",")[0]), parseFloat(src.c.split(",")[1])], parseFloat(src.c.split(",")[2]));
				object   = new ymaps.Circle(geometry, properties, options);
			}
			mframes[frm].add(object);
		}
	}
	count_objects();
	frameCounter = 1;
	for (a in mframes) {
		if (mframes.hasOwnProperty(a)) {
			if ( a > frameCounter ) {
				frameCounter = a;
			}
		}
	}
	for (a = 1; a <= frameCounter; a++) {
		if (mframes[a] === undefined) {
			mframes[a] = new ymaps.GeoObjectArray();
		}
	}
	frm = (mframes[$("#vp_frame").val()] === undefined ) ?  mframes.length - 1 : $("#vp_frame").val();
	while (mframes[frm].get(0)) {
		a_objects.add(mframes[frm].get(0));
	}
}

function normalize_style(style, type) {
	var defaults   = {
			1: 'twirl#redDotIcon',
			2: 'routes#default',
			3: 'area#default',
			4: 'circle#default',
			5: 'rct#default'
		},
		test = ymaps.option.presetStorage.get(style);

	if (test === undefined) {
		style = ["twirl", style.split("#")[1]].join("#");
		if (ymaps.option.presetStorage.get(style) === undefined) {
			console.log("Стиль оформления отсутствует в хранилище. Применены умолчания.");
			style = defaults[type];
		}
	}
	return style;
}

function action_listeners_add() {

	$(".balloonClose").unbind().click(function() {
		map.balloon.close();
	});

	if (mp !== undefined && typeof mp.id !== undefined && mp.id === 'void') {
		$(".sw-edit").addClass("hide");
		return false;
	}

	$(".sw-finish").unbind().click(function() {
		doFinish(this);
		nullTracers();
		counter = 0;
		map.balloon.close();
		count_objects();
	});

	$(".sw-edit").unbind().click(function() {
		doEdit(this);
		count_objects();
		counter = 1;
	});

	$(".sw-del").unbind().click(function() {
		doDelete(this);
		nullTracers();
		counter = 0;
		map.balloon.close();
		count_objects();
	});

	$(".copyProp").unbind().click(function() {
		toClipboard(this);
	});

	$(".pasteProp").unbind().click(function() {
		fromClipboard(this,0);
	});

	$(".pastePropOpt").unbind().click(function() {
		fromClipboard(this,1);
	});
}

function doDelete(src) {
	var ttl = parseInt($(src).attr('ttl'), 10);
	e_objects.each(function(item) {
		if (parseInt(item.properties.get("ttl"), 10) === ttl) {
			e_objects.remove(item);
		}
	});
	a_objects.each(function(item) {
		if (parseInt(item.properties.get("ttl"), 10) === ttl) {
			a_objects.remove(item);
		}
	});
	$.ajax({
		url: controller + "/obj_delete",
		data: {
			ttl: ttl
		},
		type: "POST",
		success: function() {
			console.log("The object is to be believed deleted");
		},
		error: function(data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
}

function doEdit(src) {
	var ttl;
	ttl = ($(src).attr('ttl') === undefined) ? parseInt(src.properties.get('ttl'), 10) : parseInt($(src).attr('ttl'), 10);
	$("#location_id").val(ttl); // здесь строка
	map.balloon.close();
	a_objects.each(function(item) {
		var type;
		if (parseInt(item.properties.get("ttl"), 10) === ttl) {
			e_objects.add(item);									// переводим объект в группу редактируемых
			item.balloon.open(item.geometry.getCoordinates());
			type = item.geometry.getType();							// получаем YM тип геометрии объекта
			//console.log(item.properties.getAll().toSource());
			//item.options.set({ zIndex: 1, zIndexActive: 1, zIndexDrag: 1, zIndexHover: 1, draggable: ((item.options.get('draggable') === 0) ? 1 : 0) });
			if (e_objects.getLength() > 1) { // нет особого смысла задавать вручную координаты точек, если их для редактирования выбрано больше чем одна. Отключаем поля
				$(".pointcoord, .circlecoord").prop('disabled', true);
			}
			if (type === "LineString" || type === "Polygon") {
				item.editor.startEditing();
			}
			item.options.set({ draggable: 1, zIndex: 300, zIndexActive: 300, zIndexDrag: 300, zIndexHover: 300 });
			openEditPane(type); // открываем требуемую панель редактора
			traceNode(item);
		}
	});
	action_listeners_add();
}

function doFinish(src) {
	var addr = $("#bal_addr").val(),
		desc = $("#bal_desc").val(),
		link = $("#bal_link").val(),
		name = $("#bal_name").val(),
		ttl  = parseInt($(src).attr('ttl'), 10);
	e_objects.each(function(item) {
		if (parseInt(item.properties.get("ttl"), 10) === ttl) {
			item.properties.set( {
				description : desc,
				address     : addr,
				name        : name,
				link        : link,
				hintContent : name + ' ' + addr
			} );
			a_objects.add(item);
			item.options.set({
				draggable   : 0,
				zIndex      : 1,
				zIndexActive: 1,
				zIndexDrag  : 1,
				zIndexHover : 1,
				strokeStyle : 'solid'
			});
			sendObject(item);
		}
	});
	if (e_objects.getLength() === 1) {
		$(".pointcoord, .circlecoord").removeAttr('disabled');
	}
}

function doFinishAll() {
	e_objects.each(function(item) {
		while (e_objects.getLength()) {
			a_objects.add(item); // эта операция не столько добавляет, сколько ПЕРЕМЕЩАЕТ объекты.
			item.options.set({
				draggable    : 0,
				zIndex       : 1,
				zIndexActive : 1,
				zIndexDrag   : 1,
				zIndexHover  : 1,
				strokeStyle  : 'solid'
			});
		}
	});
	count_objects();
}

function count_objects() {
	$("#ResultBody, #nowEdited").empty();
	a_objects.each(function(item) {
		$("#ResultBody").append(genListItem(item.properties.get('ttl'), item.properties.get('name'), item.properties.get('address'), gIcons[ item.geometry.getType() ]));
	});
	e_objects.each(function(item) {
		$("#nowEdited").append(genListItem(item.properties.get('ttl'), item.properties.get('name'), item.properties.get('address'), gIcons[ item.geometry.getType() ]));
	});
	$(".mg-btn-list").click(function() {
		var ttl = parseInt($(this).attr("ttl"), 10);
		a_objects.each(function(item) {
			if (parseInt(item.properties.get("ttl"), 10) === ttl) {
				item.balloon.open(item.geometry.getCoordinates());
			}
		});
		e_objects.each(function(item) {
			if (parseInt(item.properties.get("ttl"), 10) === ttl) {
				item.balloon.open( item.geometry.getCoordinates() );
				openEditPane( item.geometry.getType() );
			}
		});
	});
	action_listeners_add();
}

function openEditPane(type) {
	var intType = geoType2IntId[type];
	$("#current_obj_type").val(type);
	$(".obj_sw, #navheader li, #results").removeClass('active');
	$(".obj_sw[pr=" + intType + "], #palette, #mainselector").addClass('active');
	$(".navigator-pane").addClass("hide");
	$("#navigator-pane" + intType).removeClass("hide");
}

function nullTracers() {
	/*
	обнуление всех полей навигатора
	*/
	$("#m_lon, #m_lat, #cir_lon, #cir_lat").val('');
	$("#m_style option:first, #line_style option:first, #polygon_style option:first, #circle_style option:first").attr("selected", "selected");
	$("#m_description, #circle_description, #polygon_description, #line_description").html('');
	$("#polygon_vtx, #polygon_len, #cir_len, #cir_field, #line_vtx, #line_len").html(0);
	$("#cir_radius").val(100);
}

function traceNode(src) {
	/*
	заполнение полей навигатора характеристиками текущего редактируемого объекта
	*/
	var type   = src.geometry.getType();
	// заполнение в соответствии с типом геометрии объекта
	switch (type) {
		case "Point":
			tracePoint(src);
		break;
		case "LineString":
			tracePolyline(src);
		break;
		case "Polygon":
			tracePolygon(src);
		break;
		case "Circle":
			traceCircle(src);
		break;
	}
}

function tracePoint(src) {
	var names = [],
		valtz,
		coords = src.geometry.getCoordinates(),
		cstyle = src.properties.get("attr");
	if ($("#traceAddress").prop('checked')) {
		ymaps.geocode(coords, { kind: ['house'] })
		.then(function (res) {
			res.geoObjects.each(function (obj) {
				names.push(obj.properties.get('name'));
			});
			valtz = names[0];
			valtz = (valtz === undefined || ![valtz].join(', ').length) ? "Нет адреса" : [valtz].join(', ');
			src.properties.set({ hintContent: valtz, address: valtz });
			sendObject(src);
			count_objects();
		});
	}
	$("#m_lon").val(parseFloat(coords[0]).toFixed(8));
	$("#m_lat").val(parseFloat(coords[1]).toFixed(8));
	$("#m_style [value='" + cstyle + "']").attr("selected", "selected");
}

function tracePolyline(src) {
	var coords = src.geometry.getCoordinates(),
		cstyle = src.properties.get("attr");
	$("#line_vtx").html(src.geometry.getLength());
	$("#line_len").html(length_calc(coords) + " м.");
	$("#line_style [value='" + cstyle + "']").attr("selected", "selected");
}

function tracePolygon(src) {
	var coords = src.geometry.getCoordinates(),
		cstyle = src.properties.get("attr");
	$("#polygon_vtx").html(coords[0].length - 1);
	$("#polygon_len").html(perimeter_calc(coords) + " м.");
	$("#polygon_style [value='" + cstyle + "']").attr("selected", "selected");
}

function traceCircle(src) {
	var coords = src.geometry.getCoordinates(),
		radius = src.geometry.getRadius(),
		cstyle = src.properties.get("attr"),
		arc    = (radius * 2 * Math.PI).toFixed(2),
		field  = (Math.pow(radius,2) * Math.PI).toFixed(2);
	$("#cir_lon").val(coords[0]);
	$("#cir_lat").val(coords[1]);
	$("#cir_radius").val(radius);
	$("#cir_len").html(arc);
	$("#cir_field").html(field);
	$('#circle_style [value="' + cstyle + '"]').attr("selected", "selected");
}

function returnPreparedGeometry(item) {
	var type ,
		geometry;
	type = geoType2IntId[ item.geometry.getType() ];
	switch (type) {
		case 1:
			geometry = item.geometry.getCoordinates();
		break;
		case 2:
			geometry = ymaps.geometry.LineString.toEncodedCoordinates(item.geometry);
		break;
		case 3:
			geometry = ymaps.geometry.Polygon.toEncodedCoordinates(item.geometry);
		break;
		case 4:
			geometry = [ item.geometry.getCoordinates(), item.geometry.getRadius() ];
		break;
	}
	return geometry;
}

function sendObject(item) {
	/*
		отправка объекта на сервер
	*/
	var geometry,
		type;
	if (mp.id !== undefined && mp.id === 'void') {
		return false;
	}
	type = geoType2IntId[ item.geometry.getType() ];
	geometry = returnPreparedGeometry(item);
	$.ajax({
		url: controller + "/save",
		type: "POST",
		data: {
			id :		item.properties.get('ttl'),
			type:		type,
			geometry:	geometry,
			attr:		item.properties.get('attr'),
			desc:		item.properties.get('description'),
			address:	item.properties.get('address'),
			link:		item.properties.get('link'),
			name:		item.properties.get('name'),
			frame:		parseInt($("#vp_frame").val())
		},
		success: function() {
			console.log("The object is to be believed sent");
		},
		error: function(data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
}

function genListItem(ttl, name, address, pic) {
	var string;
	string = '<div class="btn-group">' +
		'<button class="btn btn-mini mg-btn-list" ttl=' + ttl + '>' +
		'<img src="' + api_url + '/images/' + pic + '" alt="">' + 'Название: ' + name + '<br>' +
		'Адрес: ' + address +
		'</button>' +
		'<button class="btn dropdown-toggle" data-toggle="dropdown" style="height:55px;">' +
		'<span class="caret"></span>' +
		'</button>'+
		'<ul class="dropdown-menu">' +
			'<li><a href="#" class="copyProp" ttl=' + ttl + '><i class="icon-upload"></i> Скопировать свойства</a></li>' +
			'<li><a href="#" class="pasteProp" ttl=' + ttl + ' title="Вставить свойства"><i class="icon-download"></i> Вставить свойства</a></li>' +
			'<li><a href="#" class="pastePropOpt" ttl=' + ttl + ' title="Вставить свойства и оформление"><i class="icon-download-alt"></i> Вставить всё</a></li>' +
			'<li><a href="#" class="sw-del" ttl=' + ttl + '><i class="icon-trash"></i> Удалить объект</a></li>' +
		'</ul>' +
	'</div>';
	return string;
}

function lock_center() {
	if (isCenterFixed) {
		$(".mapcoord").attr('readonly','readonly');
		$("#mapFix").html('Отслеживать центр').attr('title','Разрешить перемещать центр');
	}else{
		$(".mapcoord").removeAttr('readonly');
		$("#mapFix").html('Фиксировать центр').attr('title','Не перемещать центр');
	}
}

function setPointCoordinates() {
	/*
		ручной ввод параметров геометрии точки из полей навигатора
	*/
	e_objects.get(0).geometry.setCoordinates([parseFloat($("#m_lon").val()), parseFloat($("#m_lat").val())]);
}

function fromClipboard(src, wst) {
	/*
	вставка данных из локального буфера обмена
	*/
	var ttl = parseInt($(src).attr('ttl'), 10);
	e_objects.each(function(item) {
		if (ttl === parseInt(item.properties.get('ttl'), 10)) {
			item.properties.set({
				name        : clipboard.name,
				address     : clipboard.address,
				description : clipboard.description,
				hintContent : clipboard.name + ' ' + clipboard.address
			});
			if (wst === 1 && item.geometry.getType() === clipboard.gtype) {
				item.options.set(ymaps.option.presetStorage.get(normalize_style(clipboard.preset)));
				item.properties.set({ preset: clipboard.preset });
			}
		}
	});
	a_objects.each(function(item) {
		if (ttl === parseInt(item.properties.get('ttl'), 10)) {
			item.properties.set({
				name        :	clipboard.name,
				address     :	clipboard.address,
				description :	clipboard.description,
				hintContent :	clipboard.name + ' ' + clipboard.address
			});
			if (wst === 1 && item.geometry.getType() === clipboard.gtype) {
				item.options.set(ymaps.option.presetStorage.get(normalize_style(clipboard.preset)));
				item.properties.set({ preset: clipboard.preset });
			}
		}
	});
	count_objects();
}

function toClipboard(src) {
	/*
		помещение данных в локальный буфер обмена
	*/
	var ttl = parseInt($(src).attr('ttl'), 10);
	e_objects.each(function(item) {
		if (ttl === parseInt(item.properties.get('ttl'), 10)) {
			clipboard = {
				name:			item.properties.get('name'),
				address:		item.properties.get('address'),
				description:	item.properties.get('description'),
				preset:			item.properties.get('preset'),
				gtype:			item.geometry.getType()
			};
		}
	});
	a_objects.each(function(item) {
		if (ttl === parseInt(item.properties.get('ttl'), 10)) {
			clipboard = {
				name:			item.properties.get('name'),
				address:		item.properties.get('address'),
				description:	item.properties.get('description'),
				preset:			item.properties.get('preset'),
				gtype:			item.geometry.getType()
			};
		}
	});
	count_objects();
}

function length_calc(src) {
	/*
		расчёт длины ломаной методом прибавления дельты.
		в цикле прибавляется дельта дистанции между вершинами (WGS84)
	*/
	if (src.length < 2) {
		return 0;
	}
	var routelength = 0,
		next		= 0,
		start		= [],
		end			= [],
		delta		= 0;
	for (var i=0; i < (src.length - 1);i++) {
		next  = (i + 1);
		start = [ src[i][0],src[i][1] ];
		end   = [ src[next][0],src[next][1] ];
		delta = ymaps.coordSystem.geo.getDistance(start, end);
		routelength += delta;
	}
	routelength = (isNaN(routelength)) ? 0 : routelength;
	return routelength.toFixed(2);
}

function perimeter_calc(src) {
	/*
		расчёт длины периметра полигона методом прибавления дельты.
		в цикле прибавляется дельта дистанции между вершинами (WGS84)
		расчёт периметра геометрии, как сумма всех периметров геометрии, в том числе и внутренние границы
	*/
	if (src[0].length < 2) {
		return 0;
	}
	var routelength = 0,
		next = 0,
		start = [],
		end = [],
		delta = 0;
	for (var j = 0; j < src.length; j++) {
		for (var i=0; i < (src[j].length - 1); i++) {
			next = (i + 1);
			start = src[j][i];
			end = src[j][next];
			delta = ymaps.coordSystem.geo.getDistance(start, end);
			routelength += delta;
		}
	}
	routelength = (isNaN(routelength)) ? 0 : routelength;
	return routelength.toFixed(2);
}

function setCircleCoordinates() {
	/*
		ручной ввод параметров центра геометрии круга из полей навигатора
	*/
	var ttl = parseInt($('#location_id').val(), 10);
	e_objects.each(function(item) {
		if (parseInt(item.properties.get('ttl'), 10) === ttl) {
			item.geometry.setCoordinates([parseFloat($("#cir_lon").val()), parseFloat($("#cir_lat").val())]);
			traceNode(item);
		}
	});
}

function setCircleRadius() {
	/*
		ручной ввод параметра радиуса геометрии круга из поля навигатора
	*/
	var ttl = parseInt($('#location_id').val(), 10);
	e_objects.each(function(item) {
		if (parseInt(item.properties.get('ttl'), 10) === ttl) {
			item.geometry.setRadius(parseFloat($("#cir_radius").val()));
			traceNode(item);
		}
	});
}

function setMapCoordinates() {
	/*
		ручной ввод параметров центра карты из полей навигатора
	*/
	map.setCenter([parseFloat($("#vp_lon").val()), parseFloat($("#vp_lat").val())], parseInt($("#current_zoom").val()));
}

// события не-карты
$(".obj_sw").click(function() {
	$("#current_obj_type").val(intId2GeoType[$(this).attr('pr')]);
	$(".navigator-pane").addClass('hide');
	$("#navigator-pane" + $(this).attr('pr')).removeClass('hide');
});

$(".mg-btn-list").click(function() {
	console.log("Сделать поиск объекта!");
	//select_current_found_object(src);
});

//	### Atomic actions
$("#coordSetter").click(function() {
	setPointCoordinates();
});

$(".frame-switcher").click(function() {
	var pfrm = parseInt($("#vp_frame").val()),
		nfrm = 0;
	if (pfrm >= 1) {
		nfrm = ($(this).attr("id") === 'vp_fw') ? (pfrm + 1) : (pfrm > 1) ? (pfrm - 1) : pfrm ;
	}else{
		nfrm = 1;
	}
	hide_frame(pfrm);
	if (mframes[nfrm] === undefined) {
		mframes[nfrm] = new ymaps.GeoObjectArray();
		if (confirm("Вы создаёте новый фрейм. Следует ли скопировать содержимое предыдущего фрейма в новый?")) {
			//или другой вариант - отослать команду на сервер и перезагрузить уже клонированный фрейм...
			$.ajax({
				url: controller + "/cloneframe",
				data: {
					prev: pfrm,
					next: nfrm
				},
				dataType: "script",
				type: "POST",
				success: function() {
					place_freehand_objects(usermap);
				},
				error: function(data, stat, err) {
					console.log([ data, stat, err ]);
				}
			});
		}
	}
	show_frame(nfrm);
	$("#vp_frame").val(nfrm);
});

$(".mapcoord").blur(function() {
	setMapCoordinates();
});

$("#mapFix").click(function() {
	isCenterFixed = (isCenterFixed) ? 0 : 1;
	//lock_center();
});

$("#linkFactory a").click(function(e) {
	var mode = parseInt($(this).attr('pr'));
	if (mp === undefined) {
		console.log("Текущая карта ещё не была обработана.");
		return false;
	}
	if (mode === 1) {
		$("#mapLink").val("http://maps.korzhevdp.com" + controller + '/map/' + mp.ehash);
		$("#mapLinkContainer").removeClass("hide");
	}
	if (mode === 2) {
		$("#mapLink").val("http://maps.korzhevdp.com" + controller + '/map/' + mp.uhash);
		$("#mapLinkContainer").removeClass("hide");
	}
	if (mode === 3) {
		e.preventDefault();  //stop the browser
		$.ajax({
			url: controller + '/loadscript/' + mp.uhash,
			dataType: "html",
			type: "POST",
			success: function() {
				window.location.href = controller + '/loadscript/' + mp.uhash;
			},
			error: function(data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}
	if (mode === 4) {
		e.preventDefault();  //stop the browser
		$.ajax({
			url: controller + "/createframe/" + mp.uhash,
			dataType: "text",
			type: "POST",
			success: function() {
				$("#mapLink").val("http://maps.korzhevdp.com" + controller + '/loadframe/' + mp.uhash);
				$("#mapLinkContainer").removeClass("hide");
			},
			error: function(data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}
	if (mode === 5) {
		e.preventDefault();
		$.ajax({
			url: controller + "/transfer",
			data: {
				hash: mp.uhash
			},
			dataType: "html",
			type: "POST",
			success: function(data) {
				$("#transferCode").html(data);
				$("#transferM").modal("show");
			},
			error: function(data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}
});

$("#sessDestroy").click(function() {
	$.ajax({
		url: controller + "/session_reset",
		dataType: "script",
		type: "POST",
		success: function() {
			a_objects.removeAll();
			e_objects.removeAll();
		},
		error: function(data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
});

$("#mapReset").click(function() {
	$.ajax({
		url: controller + "/session_reset",
		dataType: "html",
		type: "POST",
		success: function() {
			a_objects.removeAll();
			e_objects.removeAll();
			$("#mapSave, #ehashID, #SContainer").css('display',"block");
		},
		error: function(data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
});

$("#linkClose").click(function() {
	$("#mapLinkContainer").addClass("hide");
});

function list_marker_styles() {
	var a;
	$("#m_style").append('<optgroup label="Объекты">');
	for (a in yandex_styles + yandex_markers) {
		if (yandex_styles.hasOwnProperty(a)) {
			$("#m_style").append( yandex_styles[a] );
		}
	}
	$("#m_style").append( '</optgroup><optgroup label="Пользовательские">');
	for (a in style_src) {
		if (style_src.hasOwnProperty(a)) {
			$("#m_style").append('<option value="' + style_src[a][2] +'">' + style_src[a][3] + '</option>');
		}
	}
	$("#m_style").append('</optgroup>');
}

function list_route_styles() {
	var a;
	for (a in style_paths) {
		if (style_paths.hasOwnProperty(a)) {
			$("#line_style").append('<option value="' + style_paths[a][2] +'">' + style_paths[a][4] + '</option>');
		}
	}
}

function list_polygon_styles() {
	var a;
	for (a in style_polygons) {
		if (style_polygons.hasOwnProperty(a)) {
			$("#polygon_style").append('<option value="' + style_polygons[a][5] +'">' + style_polygons[a][7] + '</option>');
		}
	}
}

function list_circle_styles() {
	var a;
	for (a in style_circles) {
		if (style_circles.hasOwnProperty(a)) {
			$("#circle_style").append('<option value="' + style_circles[a][7] +'">' + style_circles[a][9] + '</option>');
		}
	}
}
// Still unused FX
// Frame Control
function hide_frame(frame) {
	/*
		функция переключения фрейма
		фреймы пока упразднены, с их наследием надо разобраться
	*/
	mframes[frame].removeAll();
	while(a_objects.get(0)) {
		mframes[frame].add(a_objects.get(0));
	}
	while(e_objects.get(0)) {
		mframes[frame].add(e_objects.get(0));
	}
}

function show_frame(frm) {
	/*
		функция переключения фрейма
	*/
	while(mframes[frm].get(0)) {
		a_objects.add(mframes[frm].get(0));
	}
	//map.geoObjects.add(a_objects);
}
/*
// Fillers
function style_list() {
	var a;
	for (a in style_src) {
		if (style_src.hasOwnProperty(a)) {
			$("#m_style").append($('<option value="' + style_src[a][2] + '">' + style_src[a][3] + '</option>'));
		}
	}
}
// Nullers
function nullPlacemarkTracer() {
	$("#m_lon").val('');
	$("#m_lat").val('');
}
// Carousel
function carousel_destroy() {
	$('.modal:has(.carousel)').on('shown', function() {
		var $carousel = $(this).find('.carousel');
		if ($carousel.data('carousel') && $carousel.data('carousel').sliding) {
			$carousel.find('.active').trigger($.support.transition.end);
		}
	});
}

function carousel_init() {
	$.ajax({
		url: "/ajaxutils/getimagelist/",
		data: {
			picref: $('#l_photo').attr('picref')
		},
		type: "POST",
		cache: false,
		dataType: "html",
		success: function(data) {
			var newid = 'car_' + ($(".carousel").attr('id').split('_')[1]++);
			$("#pic_collection").empty().append(data);
			$(".carousel").attr('id', newid);
			$('#' + newid).carousel();
		},
		error: function(data, stat, err) {
			console.log("При поиске изображений произошла ошибка на сервере");
			console.log([ data, stat, err ]);
		}
	});
}
*/
