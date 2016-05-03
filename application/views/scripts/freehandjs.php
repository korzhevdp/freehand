/* jshint -W100 */
/* jshint undef: true, unused: true */
/* globals ymaps, confirm, style_src, usermap, style_paths, yandex_styles, yandex_markers, style_circles, style_polygons, styleAddToStorage */

'use strict';

var userstyles,
	ttl,
	map,
	a_objects,
	e_objects,
	counter          = 0,
	api_url          = '<?=$this->config->item("api_url");?>',
	base_url         = '<?=$this->config->item("base_url");?>',
	mainController   = 'freehand',
	expController    = 'exports',
	imageList        = [],
	mp               = {},
	clipboard        = { name: '', description: '', address: '', preset: '', gtype: "Point" },
	gIcons = {
		"Point"      : 'marker.png',
		"LineString" : 'layer-shape-polyline.png',
		"Polygon"    : 'layer-shape-polygon.png',
		"Circle"     : 'layer-shape-ellipse.png'
	},
	geoType2IntId    = {
		"Point" : 1,
		"LineString" : 2,
		"Polygon" : 3,
		"Circle" : 4,
		"Rectangle" : 5 //not used
	},
	intId2GeoType    = {
		1 : "Point",
		2 : "LineString",
		3 : "Polygon",
		4 : "Circle",
		5 : "Rectangle"  //not used
	},
	mframes          = [],
	precision        = 8,
	metricPrecision  = 2,
	isCenterFixed    = 0,
	action_listeners_add,
	doEdit;

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

function list_marker_styles() {
	var a;
	$("#m_style").append('<optgroup label="Объекты">');
	for (a in yandex_styles + yandex_markers) {
		if (yandex_styles.hasOwnProperty(a)) {
			$("#m_style").append(yandex_styles[a]);
		}
	}
	$("#m_style").append('</optgroup><optgroup label="Пользовательские">');
	for (a in userstyles) {
		if (userstyles.hasOwnProperty(a) && userstyles[a].type === 1) {
			$("#m_style").append('<option value="' + a + '">' + userstyles[a].title + '</option>');
		}
	}
	$("#m_style").append('</optgroup>');
}

function list_route_styles() {
	var a;
	for (a in userstyles) {
		if (userstyles.hasOwnProperty(a) && userstyles[a].type === 2) {
			$("#line_style").append('<option value="' + a + '">' + userstyles[a].title + '</option>');
		}
	}
}

function list_polygon_styles() {
	var a;
	for (a in userstyles) {
		if (userstyles.hasOwnProperty(a) && userstyles[a].type === 3) {
			$("#polygon_style").append('<option value="' + a + '">' + userstyles[a].title + '</option>');
		}
	}
}

function list_circle_styles() {
	var a;
	for (a in userstyles) {
		if (userstyles.hasOwnProperty(a) && userstyles[a].type === 4) {
			$("#circle_style").append('<option value="' + a + '">' + userstyles[a].title + '</option>');
		}
	}
}

function genListItem(ttl, name, address, pic) {
	return '<div class="btn-group">' +
		'<button class="btn btn-mini mg-btn-list" ttl=' + ttl + '>' +
		'<img src="' + api_url + '/images/' + pic + '" alt="">Название: ' + name + '<br>' +
		'Адрес: ' + address +
		'</button>' +
		'<button class="btn dropdown-toggle" data-toggle="dropdown" style="height:55px;">' +
		'<span class="caret"></span>' +
		'</button>' +
		'<ul class="dropdown-menu">' +
		'<li><a href="#" class="copyProp" ttl=' + ttl + '><i class="icon-upload"></i> Скопировать свойства</a></li>' +
		'<li><a href="#" class="pasteProp" ttl=' + ttl + ' title="Вставить свойства"><i class="icon-download"></i> Вставить свойства</a></li>' +
		'<li><a href="#" class="pastePropOpt" ttl=' + ttl + ' title="Вставить свойства и оформление"><i class="icon-download-alt"></i> Вставить всё</a></li>' +
		'<li><a href="#" class="sw-del" ttl=' + ttl + '><i class="icon-trash"></i> Удалить объект</a></li>' +
		'</ul>' +
	'</div>';
}

function openEditPane(type) {
	var intType = geoType2IntId[type];
	$("#current_obj_type").val(type);
	$(".obj_sw, #navheader li, #results").removeClass('active');
	$(".obj_sw[pr=" + intType + "], #palette, #mainselector").addClass('active');
	$(".navigator-pane").addClass("hide");
	$("#navigator-pane" + intType).removeClass("hide");
}

function count_objects() {
	$("#ResultBody, #nowEdited").empty();
	a_objects.each(function (item) {
		$("#ResultBody").append(genListItem(item.properties.get('ttl'), item.properties.get('name'), item.properties.get('address'), gIcons[item.geometry.getType()]));
	});
	e_objects.each(function (item) {
		$("#nowEdited").append(genListItem(item.properties.get('ttl'), item.properties.get('name'), item.properties.get('address'), gIcons[item.geometry.getType()]));
	});
	$(".mg-btn-list").click(function () {
		var ttl = $(this).attr("ttl");
		a_objects.each(function (item) {
			if (item.properties.get("ttl") === ttl) {
				item.balloon.open(item.geometry.getCoordinates());
			}
		});
		e_objects.each(function (item) {
			if (item.properties.get("ttl") === ttl) {
				item.balloon.open(item.geometry.getCoordinates());
				openEditPane(item.geometry.getType());
			}
		});
	});
	action_listeners_add();
}

function fromClipboard(src, wst) {
	/*
	вставка данных из локального буфера обмена
	*/
	var ttl = $(src).attr('ttl');
	e_objects.each(function (item) {
		if (ttl === item.properties.get('ttl')) {
			item.properties.set({
				name        : clipboard.name,
				address     : clipboard.address,
				description : clipboard.description,
				hintContent : clipboard.name + ' ' + clipboard.address
			});
			if (wst === 1 && item.geometry.getType() === clipboard.gtype) {
				item.options.set(ymaps.option.presetStorage.get(normalize_style(clipboard.preset, clipboard.gtype)));
				item.properties.set({ preset: clipboard.preset });
			}
		}
	});
	a_objects.each(function (item) {
		if (ttl === item.properties.get('ttl')) {
			item.properties.set({
				name        : clipboard.name,
				address     : clipboard.address,
				description : clipboard.description,
				hintContent : clipboard.name + ' ' + clipboard.address
			});
			if (wst === 1 && item.geometry.getType() === clipboard.gtype) {
				item.options.set(ymaps.option.presetStorage.get(normalize_style(clipboard.preset, clipboard.gtype)));
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
	var ttl = $(src).attr('ttl');
	e_objects.each(function (item) {
		if (ttl === item.properties.get('ttl')) {
			clipboard = {
			name        : item.properties.get('name'),
			address     : item.properties.get('address'),
			description : item.properties.get('description'),
			preset      : item.properties.get('preset'),
			gtype       : item.geometry.getType()
			};
		}
	});
	a_objects.each(function (item) {
		if (ttl === item.properties.get('ttl')) {
			clipboard = {
				name        : item.properties.get('name'),
				address     : item.properties.get('address'),
				description : item.properties.get('description'),
				preset      : item.properties.get('preset'),
				gtype       : item.geometry.getType()
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
	var i,
		routelength = 0,
		next        = 0,
		start       = [],
		end         = [],
		delta       = 0;
	if (src.length < 2) {
		return 0;
	}
	for (i = 0; i < (src.length - 1); i += 1) {
		next  = (i + 1);
		start = [src[i][0], src[i][1]];
		end   = [src[next][0], src[next][1]];
		delta = ymaps.coordSystem.geo.getDistance(start, end);
		routelength += delta;
	}
	routelength = (isNaN(routelength)) ? 0 : routelength;
	return routelength.toFixed(metricPrecision);
}

function perimeter_calc(src) {
	/*
	расчёт длины периметра полигона методом прибавления дельты.
	в цикле прибавляется дельта дистанции между вершинами (WGS84)
	расчёт периметра геометрии, как сумма всех периметров геометрии, в том числе и внутренние границы
	*/
	var i,
		j,
		routelength = 0,
		next        = 0,
		start       = [],
		end         = [],
		delta       = 0;
	if (src[0].length < 2) {
		return 0;
	}
	for (j = 0; j < src.length; j += 1) {
		for (i = 0; i < (src[j].length - 1); i += 1) {
			next  = (i + 1);
			start = src[j][i];
			end   = src[j][next];
			delta = ymaps.coordSystem.geo.getDistance(start, end);
			routelength += delta;
		}
	}
	routelength = (isNaN(routelength)) ? 0 : routelength;
	return routelength.toFixed(metricPrecision);
}

function doDelete(src) {
	var ttl = $(src).attr('ttl');
	e_objects.each(function (item) {
		if (item.properties.get("ttl") === ttl) { // !!!!!!!!!!!!!!!!! === OR == ?
			e_objects.remove(item);
		}
	});
	a_objects.each(function (item) {
		if (item.properties.get("ttl") === ttl) {
			a_objects.remove(item);
		}
	});
	$.ajax({
		url: controller + "/deleteobject",
		data: {
			ttl: ttl
		},
		type: "POST",
		success: function () {
			console.log("The object is to be believed deleted");
		},
		error: function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
}

function returnPreparedGeometry(item) {
	/* неявненько получилось геометрию вернуть */
	var type = geoType2IntId[item.geometry.getType()],
	fx   = {
		1 : function () { return item.geometry.getCoordinates(); },
		2 : function () { return ymaps.geometry.LineString.toEncodedCoordinates(item.geometry); },
		3 : function () { return ymaps.geometry.Polygon.toEncodedCoordinates(item.geometry); },
		4 : function () { return [item.geometry.getCoordinates(), item.geometry.getRadius()]; }
	};
	return fx[type]();
}

function sendObject(item) {
	/* отправка объекта на сервер */
	var type     = geoType2IntId[item.geometry.getType()],
		geometry = returnPreparedGeometry(item);
	if (mp.id !== undefined && mp.id === 'void') {
		return false;
	}
	$.ajax({
		url          : '/' + mainController + "/save",
		type         : "POST",
		data         : {
			id       : item.properties.get('ttl'),
			type     : type,
			geometry : geometry,
			attr     : item.properties.get('attr'),
			desc     : item.properties.get('description'),
			address  : item.properties.get('address'),
			link     : item.properties.get('link'),
			name     : item.properties.get('name'),
			images   : item.properties.get('imageList'),
			frame    : parseInt($("#vp_frame").val(), 10)
		},
		success: function () {
			console.log("The object is to be believed sent");
		},
		error: function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
}

function doFinish(src) {
	var addr = $("#bal_addr").val(),
		desc = $("#bal_desc").val(),
		link = $("#bal_link").val(),
		name = $("#bal_name").val(),
		ttl  = $(src).attr('ttl');
	e_objects.each(function (item) {
		if (item.properties.get("ttl") === ttl) {
			item.properties.set({
				description : desc,
				address     : addr,
				name        : name,
				link        : link,
				hintContent : name + ' ' + addr
			});
			a_objects.add(item);
			item.options.set({
				draggable   : 0,
				zIndex      : 1,
				zIndexActive: 1,
				zIndexDrag  : 1,
				zIndexHover : 1
			});
			sendObject(item);
		}
	});
	if (e_objects.getLength() === 1) {
		$(".pointcoord, .circlecoord").removeAttr('disabled');
	}
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

function action_listeners_add() {
	$(".balloonClose").unbind().click(function () {
		map.balloon.close();
	});

	if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
		$(".sw-edit").addClass("hide");
		return false;
	}

	$(".sw-finish").unbind().click(function () {
		doFinish(this);
		nullTracers();
		counter = 0;
		map.balloon.close();
		count_objects();
	});

	$(".sw-edit").unbind().click(function () {
		doEdit(this);
		count_objects();
		counter = 1;
	});

	$(".sw-del").unbind().click(function () {
		doDelete(this);
		nullTracers();
		counter = 0;
		map.balloon.close();
		count_objects();
	});

	$("#imgUploader").unbind().click(function () {
		$("#mainForm").addClass("hide");
		$("#uploadForm").removeClass("hide");
	});

	$("#toMain").unbind().click(function () {
		$("#uploadForm").addClass("hide");
		$("#mainForm").removeClass("hide");
	});

	$("#addUploadItem").unbind().click(function(){
		var leng = $("#uForm :file").length;
		$("#uForm").append('<input type="file" name="file' + leng + '" id="file' + leng + '"><button type="button" class="btn delPicture" id="del' + leng + '" ref=' + leng + '><i class="icon-minus"></i></button>');
		if ($("#uForm :file").length) {
			$("#uploadImages").removeClass("disabled").prop("disabled", false);
		}
		$(".delPicture").unbind().click(function() {
			var id = $(this).attr("ref");
			$("#file"+ id + ", #del" + id).remove();
			if (!$("#uForm :file").length) {
				$("#uploadImages").addClass("disabled").prop("disabled", true);
			}
		});
	});

	$("#uploadImages").unbind().click(function(){
		var data = new FormData($("#uForm")[0]);
		$.ajax({
			url         : '/upload/files',
			dataType    : 'script',
			data        : data,
			cache       : false,
			contentType : false,
			processData : false,
			type        : 'POST',
			success     : function(data){
				if (parseInt(uploadresult.status, 10) === 1) {
					$("#uploadForm").addClass("hide");
					$("#mainForm").removeClass("hide");
					getsessionImages();
				}
				if (parseInt(uploadresult.status, 10) === 0) {
					$("#uploadError").html(uploadresult.error);
					$("#uploadError").removeClass("hide");
				}
			}
		});
	});

	$(".copyProp").unbind().click(function () {
		toClipboard(this);
	});

	$(".pasteProp").unbind().click(function () {
		fromClipboard(this, 0);
	});

	$(".pastePropOpt").unbind().click(function () {
		fromClipboard(this, 1);
	});

	$("#imgSelector").unbind().click(function(){
		showImageSelector();
	})
}

function showImageSelector() {
	$.ajax({
		url         : '/mapmanager/listuserimages',
		type        : 'GET',
		dataType    : 'html',
		success     : function(data){
			$("#imageList").empty().append(data);
			$("#imageList li").click(function(){
				if ($(this).hasClass("active")) {
					$(this).removeClass("active");
					//return false;
				} else {
				//if (!$(this).hasClass("active")) {
					$(this).addClass("active");
					//return false;
				}
				imageList = [];
				$("#imageList li.active").each(function() {
					imageList.push($(this).attr("file"));
				});
				//alert(imageList.toSource());
			});
		}
	});
	$("#imageM").modal("show");
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
		});
	}
	sendObject(src);
	count_objects();
	$("#m_lon").val(parseFloat(coords[0]).toFixed(precision));
	$("#m_lat").val(parseFloat(coords[1]).toFixed(precision));
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
		arc    = (radius * 2 * Math.PI).toFixed(metricPrecision),
		field  = (Math.pow(radius, 2) * Math.PI).toFixed(metricPrecision);
	$("#cir_lon").val(coords[0]);
	$("#cir_lat").val(coords[1]);
	$("#cir_radius").val(radius);
	$("#cir_len").html(arc);
	$("#cir_field").html(field);
	$('#circle_style [value="' + cstyle + '"]').attr("selected", "selected");
}

function traceNode(src) {
	/* заполнение полей навигатора характеристиками текущего редактируемого объекта в соответствии с типом геометрии объекта */
	var type   = src.geometry.getType(),
		fx     = {
			"Point"      : function (src) { tracePoint(src); },
			"LineString" : function (src) { tracePolyline(src); },
			"Polygon"    : function (src) { tracePolygon(src); },
			"Circle"     : function (src) { traceCircle(src); }
		};
	fx[type](src);
}

function doEdit(src) {
	//alert($(src).attr('ttl'));
	var ttl = $(src).attr('ttl');
	$("#location_id").val(ttl); // здесь строка
	map.balloon.close();
		a_objects.each(function (item) {
			//alert(parseInt(item.properties.get("ttl"), 10) + " --- " + ttl)
			if (item.properties.get("ttl") === ttl) {
				var type = item.geometry.getType(); // получаем YM тип геометрии объекта
				e_objects.add(item); // переводим объект в группу редактируемых
				item.balloon.open(item.geometry.getCoordinates());
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

function doFinishAll() {
	e_objects.each(function (item) {
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

function lock_center() {
	if (isCenterFixed) {
		$(".mapcoord").attr('readonly', 'readonly');
		$("#mapFix").html('Отслеживать центр').attr('title', 'Разрешить перемещать центр');
	}
	if (!isCenterFixed) {
		$(".mapcoord").removeAttr('readonly');
		$("#mapFix").html('Фиксировать центр').attr('title', 'Не перемещать центр');
	}
}

function setPointCoordinates() {
	/* ручной ввод параметров геометрии точки из полей навигатора */
	e_objects.get(0).geometry.setCoordinates([parseFloat($("#m_lon").val()), parseFloat($("#m_lat").val())]);
}

function setCircleCoordinates() {
	/*
	ручной ввод параметров центра геометрии круга из полей навигатора
	*/
	var ttl = $('#location_id').val();
	e_objects.each(function (item) {
		if (item.properties.get('ttl') === ttl) {
			item.geometry.setCoordinates([parseFloat($("#cir_lon").val()), parseFloat($("#cir_lat").val())]);
			traceNode(item);
		}
	});
}

function setCircleRadius() {
	/*
	ручной ввод параметра радиуса геометрии круга из поля навигатора
	*/
	var ttl = $('#location_id').val();
	e_objects.each(function (item) {
		if (item.properties.get('ttl') === ttl) {
			item.geometry.setRadius(parseFloat($("#cir_radius").val()));
			traceNode(item);
		}
	});
}

function setMapCoordinates() {
	/*
	ручной ввод параметров центра карты из полей навигатора
	*/
	map.setCenter([parseFloat($("#vp_lon").val()), parseFloat($("#vp_lat").val())], parseInt($("#current_zoom").val(), 10));
}

function place_freehand_objects(source) {
	var src,
		object,
		geometry,
		options,
		properties,
		b,
		a,
		frameCounter,
		frm;
	for (b in source) {
		if (source.hasOwnProperty(b)) {
			src = source[b];
			options = ymaps.option.presetStorage.get(normalize_style(src.a, src.p));
			frm = (src.frame === undefined) ? 1 : parseInt(src.frame, 10);
			properties = {
				attr        : src.a,
				description : src.d,
				address     : src.b,
				hintContent : src.n + ' ' + src.d,
				img         : src.i[0],
				frame       : frm,
				link        : src.l,
				name        : src.n,
				ttl         : b,
				images      : src.i.join(" ")
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
			if (a > frameCounter) {
				frameCounter = a;
			}
		}
	}
	for (a = 1; a <= frameCounter; a += 1) {
		if (mframes[a] === undefined) {
			mframes[a] = new ymaps.GeoObjectArray();
		}
	}
	frm = (mframes[$("#vp_frame").val()] === undefined) ?  mframes.length - 1 : $("#vp_frame").val();
	while (mframes[frm].get(0)) {
		a_objects.add(mframes[frm].get(0));
	}
}

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
		forIFrame      = 0,
		object_gid     = parseInt($("#gCounter").val(), 10),
		map_center     = $("#map_center").val(),
		lon            = (isNaN(ymaps.geolocation.longitude)) ? parseFloat(map_center.toString().split(",")[0]) : ymaps.geolocation.longitude,
		lat            = (isNaN(ymaps.geolocation.latitude))  ? parseFloat(map_center.toString().split(",")[1]) : ymaps.geolocation.latitude,
		current_zoom   = ($("#current_zoom").val().length)    ? $("#current_zoom").val() : 15,
		tileServerID   = parseInt(Math.random() * (4 - 1) + 1, 10).toString(),
		tileServerLit  = { "0": "a", "1": "b", "2": "c", "3": "d", "4": "e", "5": "f" },
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

		// http://stackoverflow.com/questions/5392344/sending-multipart-formdata-with-jquery-ajax
		editBalloon = ymaps.templateLayoutFactory.createClass(
		'<div class="ymaps_balloonX">' +
		'<div id="mainForm" class="">' +
		'<label>Название:<input type="text" id="bal_name" value="$[properties.name|без имени]"></label>' +
		'<label>Адрес:<input type="text" id="bal_addr" placeholder="Правый щелчок по карте добавит адрес места" value="$[properties.address|нет]"></label>' +
		'<label>Ссылка:' +
		'<input type="text" id="bal_link" placeholder="Ссылка на web-страницу или изображение" value="$[properties.link|#]">' +
		'</label>' +
		'<label for="a2232G">Фото:' +
		'<span id="locationImages">' +
		'<img src="' + base_url + 'storage/korzhevdp/32/b737E.jpeg"><img src="' + base_url + '/storage/korzhevdp/32/b737E.jpeg"><img src="' + base_url + '/storage/korzhevdp/32/b737E.jpeg">' +
		'<div class="btn-group" style="float:right;margin-top: 4px;">' +
		'<button class="btn" type="button" id="imgSelector" title="Выбрать изображения"><i class="icon-picture"></i></button>' +
		'<button class="btn" type="button" id="imgUploader" title="Загрузить изображения"><i class="icon-upload"></i></button>' +
		'</div>' +
		'</span>' +
		'</label>' +

		'<label><textarea placeholder="Описание..." id="bal_desc" rows="6" cols="6">$[properties.description|нет]</textarea></label>' +
		'<div class="pull-right">' +
		'<button type="button" class="btn btn-mini btn-primary sw-finish" ttl="$[properties.ttl|0]" style="margin-right:10px;">Готово</button>' +
		'<button type="button" class="btn btn-mini btn-danger sw-del" ttl="$[properties.ttl|0]" style="margin-right:10px;">Удалить</button>' +
		'<button type="button" class="btn btn-mini btn-info balloonClose">Закрыть</button>' +
		'</div>' +
		'</div>' +
		'<div id="uploadForm" class="hide"><h4>Загрузить изображения<button type="button" id="toMain" class="btn pull-right" title="Вернуться к свойствам"><i class="icon-list" ></i></button></h4>' +
		'<form method="post" id="uForm" action="/upload/files">' +
		'<input type="file" name="file0" id="file0"><button type="button" class="btn delPicture" id="del0" ref=0><i class="icon-minus"></i></button>' +
		'<button type="button" id="addUploadItem" class="btn pull-right" title="Добавить файл"><i class="icon-plus"></i></button>' +
		'</form>' +
		'<button type="button" id="uploadImages" class="btn btn-info btn-block" title="Добавить файл">Загрузить изображения</button>' +
		'<div class="alert alert-info hide" id="uploadError"></div>' +
		'</div>' +
		'</div>'
		);

	function showAddress(e) {
		var names = [],
			coords = e.get('coordPosition');
		ymaps.geocode(coords, {kind: ['house']}).then(function (res) {
			res.geoObjects.each(function (obj) {
				names.push(obj.properties.get('name'));
			});
			valtz = (names[0] !== undefined) ? [names[0]].join(', ') : "Нет адреса";
			if (!map.balloon.isOpen()) {
				map.balloon.open(coords, {
					contentBody: '<div class="ymaps_balloon row-fluid"><input type="text" value="' + [ coords[0].toPrecision(precision), coords[1].toPrecision(precision)].join(', ') + '"><br>' + valtz + '</div>'
				});
			}
			if (map.balloon.isOpen()) {
				$("#bal_addr").val(valtz);
			}
		});
	}

	function apply_preset(src, style) {
		src.options.set(ymaps.option.presetStorage.get(style)); // назначение стиля в опции.
		src.properties.set({ attr: style }); // параллельная запись определения в свойства.
		sendObject(src);
	}

	function draw_object(click) {
		var geometry,
			properties,
			object,
			decAddr,
			names = [],
			valtz = '',
			coords = click.get('coordPosition'),
			selectors = {
				1 : '#m_style',
				2 : '#line_style',
				3 : '#polygon_style',
				4 : '#circle_style',
				5 : ''
			},
			pr_type = geoType2IntId[$("#current_obj_type").val()],
			realStyle = normalize_style($(selectors[pr_type]).val(), pr_type),
			options;
			options = ymaps.option.presetStorage.get(realStyle);
			properties = {
				attr        : realStyle,
				frame       : parseInt($('#vp_frame').val(), 10),
				ttl         : object_gid += 1,
				name        : "Название",
				img         : "nophoto.gif",
				hintContent : '',
				address     : '',
				contact     : '',
				description : '',
				imageList   : []
			};
		if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
			console.log("Рисование запрещено");
			return false;
		}
		if (pr_type === 0) {
			console.log("Ошибка в декодировании типа объекта. 0 не является допустимым типом");
			return false;
		}
		if (counter) {
			if (!confirm("На карте присутствуют редактируемые объекты.\nЗавершить их редактирование и создать новый объект?")) {
				return false;
			}
			doFinishAll();
		}

		ymaps.geocode(coords, {kind: ['house']}).then(function (res) {
			res.geoObjects.each(function (obj) {
				names.push(obj.properties.get('name'));
			});
			valtz = names[0];
			decAddr = (valtz === undefined || ![valtz].join(', ').length) ? "Нет адреса" : [valtz].join(', ');
			properties.description = decAddr;
			properties.hintContent = decAddr;
			properties.address     = decAddr;
		});

		switch (pr_type) {
			case 1:
				geometry = { type: "Point", coordinates: click.get('coordPosition') };
				object   = new ymaps.Placemark(geometry, properties, options);
				traceNode(object);
			break;
			case 2:
				geometry = { type: 'LineString', coordinates: [click.get('coordPosition')] };
				object   = new ymaps.Polyline(geometry, properties, options);
				sendObject(object);
			break;
			case 3:
				geometry = { type: 'Polygon', coordinates: [[click.get('coordPosition')]] };
				object   = new ymaps.Polygon(geometry, properties, options);
				sendObject(object);
			break;
			case 4:
				geometry = [click.get('coordPosition'), $("#cir_radius").val()];
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
		counter += 1;
		$('#location_id').val(object_gid);
		count_objects();
	}

	function loadmap(name) {
		if (!name.length) {
			alert("Введите идентификатор карты");
			return false;
		}
		$.ajax({
			url      : '/' + mainController + "/loadmap",
			type     : "POST",
			data     : {
				name : name
			},
			dataType : "script",
			success  : function () {
				if (usermap.error !== undefined) {
					console.log(usermap.error);
					return false;
				}
				a_objects.removeAll();
				e_objects.removeAll();
				if (usermap.error === undefined) {
					place_freehand_objects(usermap);
				}
				if (mp !== undefined) {
					$("#mapSave, #ehashID, #SContainer").css('display', ((mp.id === 'void') ? 'none' : 'block'));
					map.setType(mp.maptype).setZoom(mp.zoom).panTo(mp.c);
				}
				count_objects();
				lock_center();
			},
			error    : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}

	function loadSessionData() {
		/*
		загрузка данных сессии
		*/
		$.ajax({
			url      : '/' + mainController + "/getsession",
			dataType : "script",
			type     : "POST",
			success  : function () {
				place_freehand_objects(usermap);
			},
			error: function (data, stat, err) {
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
			url      : '/' + mainController + "/savedb",
			type     : "POST",
			dataType : "script",
			success  : function () {
				a_objects.removeAll();
				e_objects.removeAll();
				place_freehand_objects(usermap);
				count_objects();
			},
			error: function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}

	function sendMap() {
		if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
			return false;
		}
		$.ajax({
			url     : '/' + mainController + "/savemap",
			type    : "POST",
			data    : {
				maptype : map.getType(),
				center  : [ $("#vp_lat").val(), $("#vp_lon").val() ],
				zoom    : map.getZoom()
			},
			datatype    : "text",
			success     : function () {
				console.log("Data sent");
			},
			error       : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}
	//определение механизма пересчёта стандартной сетки тайлов в сетку тайлов Яндекс-карт (TMS)
	//api_url = (typeof $("#api_url") != 'undefined' && $("#api_url").val().length) ? $("#api_url").val() : "http://api.arhcity.ru",
	for (a = 0; a < 21; a += 1) {
		dX[a] = Math.pow(2, a) - 1;
	}
	layerTypes   = {
		/*
		0: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[0].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/nm/base/",
			label : "base#nm",
			name  : "Нарьян-Мар 1943 HD",
			layers: ['yandex#satellite', "base#nm"]
		},
		1: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[1].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/1990/",
			label : "base2#arch",
			name  : "Архангельск. План 1998 года",
			layers: ['yandex#satellite', "base2#arch"]
		},

		2: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[2].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/arch1940/base/",
			label : "base3#arch",
			name  : "Архангельск. 1941-43 гг. Стандартное разрешение",
			layers: ['yandex#satellite', "base3#arch"]
		},
		3: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[3].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/arch1940/centerhr/",
			label : "base4#arch",
			name  : "Архангельск. 1941-43 гг. Центр. Высокое разрешение",
			layers: ['yandex#satellite', "base4#arch"]
		},
		4: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[4].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/arch1940/farnorth/",
			label : "base5#arch",
			name  : "Архангельск. 1941-43 гг. Север, фрагменты. Высокое разрешение",
			layers: ['yandex#satellite', "base5#arch"]
		},
		5: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[5].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/molotowsk/Molotowsk041/",
			label : "base#molot",
			name  : "Молотовск и окрестности 25.04.1943 г.",
			layers: ['yandex#satellite', "base#molot"]
		},
		6: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[6].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/molotowsk/Molotowsk040/",
			label : "base#molot2",
			name  : "Молотовск, центр города 25.04.1943 г.",
			layers: ['yandex#satellite', "base#molot", "base#molot2"]
		},
		7: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[7].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/molotowsk/Molotowsk042/",
			label : "base#molot3",
			name  : "Молотовск. Завод. 8.07.1943 г.",
			layers: ['yandex#satellite', "base#molot", "base#molot3"]
		},
		8: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[8].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "Molotowsk044/",
			label : "base#molot4",
			name  : "Молотовск. Завод. Ягры. 15.08.1943 г.",
			layers: ['yandex#satellite', "base#molot", "base#molot4"]
		},
		9: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return layerTypes[9].folder + zoom + '/' + tile[0] + '/' + (dX[zoom] - tile[1]) + '.png'; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "http://luft.korzhevdp.com/maps/molotowsk/Molotowsk049/",
			label : "base#molot5",
			name  : "Молотовск. Завод. 15.08.1943 г.",
			layers: ['yandex#satellite', "base#molot5"]
		},
		*/
		10: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return "http://mt" + tileServerID + ".google.com/vt/lyrs=m&hl=ru&x=" + tile[0] + "&y=" + tile[1] + "&z=" + zoom + "&s=Galileo"; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "",
			label : "map#google",
			name  : "Схема местности, Google",
			layers: ["map#google"]
		},
		11: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return "http://" + tileServerLit[tileServerID] + ".tile.openstreetmap.org/" + zoom + "/" + tile[0] + "/" + tile[1] + ".png"; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "",
			label : "map#osm",
			name  : "Схема местности, OSM",
			layers: ["map#osm"]
		},
		/*
		12: {
			func  : '',
			folder: "",
			label : "default#map",
			name  : "По умолчанию",
			layers: ["yandex#satellite"]
		},
		*/
		13: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return "http://mt" + tileServerID + ".google.com/vt/lyrs=s&hl=ru&x=" + tile[0] + "&y=" + tile[1] + "&z=" + zoom + "&s=Galileo"; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "",
			label : "satellite#google",
			name  : "Аэрофотосъёмка, Google",
			layers: ["satellite#google"]
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
			ymaps.mapType.storage.add(layerTypes[a].label, new ymaps.MapType(layerTypes[a].name, layerTypes[a].layers));
			typeSelector.addMapType(layerTypes[a].label, a);
		}
	}

	map = new ymaps.Map("YMapsID", {
		center               : [lon, lat],//(map_center.length) ? [ parseFloat(map_center.split(",")[1]), parseFloat(map_center.split(",")[0]) ] : [lon, lat],
		zoom                 : current_zoom,
		//type                 : current_type,
		type                 : 'yandex#satellite',
		behaviors            : ["scrollZoom", "drag", "dblClickZoom"]
	},
	{
		maxZoom              : 19,
		projection           : ymaps.projection.sphericalMercator,
		suppressMapOpenBlock : true,
		yandexMapAutoSwitch  : false
	});

	/* назначаем курсор-стрелку для улучшенного позиционирования */
	cursor = map.cursors.push('crosshair', 'arrow');
	cursor.setKey('arrow');

	/* ViewPort data fields */
	viewPort = {
		frame  : 1,
		c_c    : [ map.getCenter()[0].toFixed(precision), map.getCenter()[1].toFixed(precision) ],
		zoom   : 13,
		c_Type : 'yandex#satellite'
	};

	$("#vp_frame").val(1);
	$("#vp_lon").val(map.getCenter()[0].toFixed(precision));
	$("#vp_lat").val(map.getCenter()[1].toFixed(precision));
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
	ymaps.layout.storage.add('iframe#balloonLayout',  iframeBalloon);
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
			$("#vp_lon").val(data.get('newCenter')[0].toFixed(precision)); // сохраняем в поле новое значение центра карты
			$("#vp_lat").val(data.get('newCenter')[1].toFixed(precision)); // сохраняем в поле новое значение центра карты
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
		showAddress(e);
	});

	e_objects.events.add('dragend', function (e) {
		var object = e.get('target');
		traceNode(object);
	});

	a_objects.events.add('contextmenu', function (e) {
		if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
			return false;
		}
		object = e.get('target');
		doEdit(object);
		count_objects();
		counter = 1;
	});

	a_objects.events.add('click', function (e) {
		var object = e.get('target');
		if (e_objects.getLength() !== 1) {
			return false;
		}
		e_objects.each(function (item) {
			var auxGeometry;
			if (item.geometry.getType() === "LineString") {
				item.geometry.insert(item.geometry.getLength() + 1, object.geometry.getCoordinates());
			}
			if (item.geometry.getType() === "Polygon") {
				auxGeometry = item.geometry.getCoordinates();
				auxGeometry[0][auxGeometry[0].length - 1] = object.geometry.getCoordinates();
				item.geometry.setCoordinates(auxGeometry);
			}
		});
		map.balloon.close();
	});

	e_objects.events.add('contextmenu', function (e) {
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

	$("#m_style, #line_style, #polygon_style, #circle_style").change(function () {
		var val = $(this).val();
		e_objects.each(function (item) {
			apply_preset(item, val);
		});
	});

	$("#mapLoader").click(function () {
		loadmap($("#mapName").val());
	});

	$("#mapSave").click(function () {
		saveAll();
	});

	// установка параметров круга
	$(".circlecoord").blur(function () {
		setCircleCoordinates();
	});
	$("#cir_radius").keyup(function () {
		setCircleRadius();
	});
	// последняя функция процессора карты - загрузка карты по id из строки браузера #################################
	if ($("#maphash").val().length === 16) {
		loadmap($("#maphash").val());
		$("#mapName").val($("#maphash").val());
	}
	if ($("#maphash").val().length !== 16) {
		loadSessionData();
	}
}

function setup_environment() {
	styleAddToStorage(userstyles);
	list_marker_styles();
	list_route_styles();
	list_polygon_styles();
	list_circle_styles();
	display_locations();
}

function hide_frame(frame) {
	/*
	функция переключения фрейма
	фреймы пока упразднены, с их наследием надо разобраться
	*/
	mframes[frame].removeAll();
	while (a_objects.get(0)) {
		mframes[frame].add(a_objects.get(0));
	}
	while (e_objects.get(0)) {
		mframes[frame].add(e_objects.get(0));
	}
}

function show_frame(frm) {
	/*
	функция переключения фрейма
	*/
	while (mframes[frm].get(0)) {
		a_objects.add(mframes[frm].get(0));
	}
	//map.geoObjects.add(a_objects);
}

function syncToSession(usermap){
	$.ajax({
		url          : '/' + mainController + "/synctosession",
		type         : "POST",
		data         : usermap,
		success      : function () {
			console.log("The objects are to be believed sent");
		},
		error        : function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
}

function getsessionImages() {
	$.ajax({
		url          : '/mapmanager/listuserimages',
		type         : "GET",
		dataType     : 'html',
		success      : function (data) {
			$("#sessionImages").append(data);
		},
		error        : function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
}

// события не-карты
$(".obj_sw").click(function () {
	$("#current_obj_type").val(intId2GeoType[$(this).attr('pr')]);
	$(".navigator-pane").addClass('hide');
	$("#navigator-pane" + $(this).attr('pr')).removeClass('hide');
});

$(".mg-btn-list").click(function () {
	console.log("Сделать поиск объекта!");
	//select_current_found_object(src);
});

// ### Atomic actions
$("#coordSetter").click(function () {
	setPointCoordinates();
});

$(".frame-switcher").click(function () {
	var pfrm = parseInt($("#vp_frame").val(), 10),
		nfrm = 0;
	if (pfrm >= 1) {
		nfrm = ($(this).attr("id") === 'vp_fw') ? (pfrm + 1) : (pfrm > 1) ? (pfrm - 1) : pfrm;
	} else {
		nfrm = 1;
	}
	hide_frame(pfrm);
	if (mframes[nfrm] === undefined) {
		mframes[nfrm] = new ymaps.GeoObjectArray();
		if (confirm("Вы создаёте новый фрейм. Следует ли скопировать содержимое предыдущего фрейма в новый?")) {
			//или другой вариант - отослать команду на сервер и перезагрузить уже клонированный фрейм...
			$.ajax({
				url      : '/' + mainController + "/cloneframe",
				data     : {
					prev : pfrm,
					next : nfrm
				},
				dataType : "script",
				type     : "POST",
				success  : function () {
					place_freehand_objects(usermap);
				},
				error    : function (data, stat, err) {
					console.log([ data, stat, err ]);
				}
			});
		}
	}
	show_frame(nfrm);
	$("#vp_frame").val(nfrm);
});

$(".mapcoord").blur(function () {
	setMapCoordinates();
});

$("#mapFix").click(function () {
	isCenterFixed = (isCenterFixed) ? 0 : 1;
	//lock_center();
});

$("#linkFactory a").click(function (e) {
	var mode = parseInt($(this).attr('pr'), 10);
	if (mp === undefined) {
		console.log("Текущая карта ещё не была обработана.");
		return false;
	}
	if (mode === 1) {
		$("#mapLink").val(base_url + 'map/' + mp.ehash);
		$("#mapLinkContainer").removeClass("hide");
	}
	if (mode === 2) {
		$("#mapLink").val(base_url + 'map/' + mp.uhash);
		$("#mapLinkContainer").removeClass("hide");
	}
	if (mode === 3) {
		e.preventDefault();  //stop the browser
		$.ajax({
			url      : expController + '/loadscript/' + mp.uhash,
			dataType : "html",
			type     : "POST",
			success  : function () {
				window.location.href = expController + '/loadscript/' + mp.uhash;
			},
			error    : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}
	if (mode === 4) {
		e.preventDefault();  //stop the browser
		$.ajax({
			url      : expController + "/createframe/" + mp.uhash,
			dataType : "script",
			type     : "POST",
			success  : function () {
				$("#mapLinkContainer").removeClass("hide");
				if (parseInt(createFrame.status, 10) === 0) {
					$("#mapLink").val(createFrame.error);
					return false;
				}
				$("#mapLink").val(base_url + expController + '/loadframe/' + mp.uhash);
			},
			error    : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}
	if (mode === 5) {
		e.preventDefault();
		$.ajax({
			url      : expController + "/transfer",
			data     : {
				hash : mp.uhash
			},
			dataType : "html",
			type     : "POST",
			success  : function (data) {
				$("#transferCode").html(data);
				$("#transferM").modal("show");
			},
			error    : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}
	/*
	MTAyZjI1OTAxNTg3_d22f0a7d: 1 : { d: 'обрыв кабеля 29.01.2015 и 2.02.2015 отметка по кабелю 2254 м.', n: ' Обрыв', a: 'twirl#truckIcon', p: 1, c: '40.51192676098229,64.59129951947355', b: 'улица Мещерского, 1 с1', l: '' },, b: '', l: '', n: '' },

	mp = { id: '', maptype: 'yandex#map', c: [40.56577018,64.55124603], zoom: 11, uhash: '', ehash: '', indb: 0 };
	usermap = {
	1 : { d: '1.Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5594748,40.5249837', b: '', l: '', n: '' },
	2 : { d: '2. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5594022,40.5249327', b: '', l: '', n: '' },
	3 : { d: '3. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5566459,40.5235058', b: '', l: '', n: '' },
	4 : { d: '4. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5535414,40.5213279', b: '', l: '', n: '' },
	5 : { d: '5.Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5534308,40.5212688', b: '', l: '', n: '' },
	6 : { d: '6. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5529283,40.5213869', b: '', l: '', n: '' },
	7 : { d: '7. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5528499,40.5213279', b: '', l: '', n: '' },
	8 : { d: '8.Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5477116,40.5187261', b: '', l: '', n: '' },
	9 : { d: '9. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5478269,40.5188119', b: '', l: '', n: '' },
	10 : { d: '10. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5443778,40.5169129', b: '', l: '', n: '' },
	11 : { d: '11. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5441841,40.5167413', b: '', l: '', n: '' },
	12 : { d: '12. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5364682,40.5176961', b: '', l: '', n: '' },
	13 : { d: '13. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5340925,40.5228997', b: '', l: '', n: '' },
	14 : { d: '14 Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5340233,40.5231625', b: '', l: '', n: '' },
	15 : { d: '15. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5343485,40.523538', b: '', l: '', n: '' },
	16 : { d: '16. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5344062,40.5233395', b: '', l: '', n: '' },
	17 : { d: '17. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5344938,40.523141', b: '', l: '', n: '' },
	18 : { d: '18. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5347983,40.5224329', b: '', l: '', n: '' },
	19 : { d: '19. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5348421,40.5223042', b: '', l: '', n: '' },
	20 : { d: '20. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5318851,40.5316812', b: '', l: '', n: '' },
	21 : { d: '21. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5341548,40.5250937', b: '', l: '', n: '' },
	22 : { d: '22. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5376651,40.5161136', b: '', l: '', n: '' },
	23 : { d: '23. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5506347,40.5732876', b: '', l: '', n: '' },
	24 : { d: '24. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5513309,40.5733788', b: '', l: '', n: '' },
	25 : { d: '25. Торговый павильон (4 шт)', a: 'twirl#truckIcon', p: 1, c: '64.5502129,40.5746502', b: '', l: '', n: '' },
	26 : { d: '26. Торговый павильон (4 шт)', a: 'twirl#truckIcon', p: 1, c: '64.5507638,40.5729175', b: '', l: '', n: '' },
	27 : { d: '27. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.550372,40.5734217', b: '', l: '', n: '' },
	28 : { d: '28. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5493761,40.5686045', b: '', l: '', n: '' },
	29 : { d: '29. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.5499501,40.5664533', b: '', l: '', n: '' },
	30 : { d: '31. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5495951,40.5675155', b: '', l: '', n: '' },
	31 : { d: '30. Торговый киоск', a: 'twirl#truckIcon', p: 1, c: '64.54898650000001,40.5642539', b: '', l: '', n: '' },
	32 : { d: '32. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5486338,40.5615664', b: '', l: '', n: '' },
	33 : { d: '33. Торговый павильон', a: 'twirl#truckIcon', p: 1, c: '64.5623517,40.5306244', b: '', l: '', n: '' }
	}
	*/
	if (mode === 6) {
		e.preventDefault();
		$("#importM").modal("show");
		$("#importBtn").unbind().click(function (){
			var a;
			eval($("#importCode").val());
			if( $("#cRev").prop("checked") ) {
				for (a in usermap ) {
					//alert(usermap[a].c);
					if (usermap.hasOwnProperty(a)){
						usermap[a].c = usermap[a].c.split(",").reverse().join(",");
					}
					//alert(usermap[a].c);
				}
			}
			place_freehand_objects(usermap);
			syncToSession(usermap);
			$("#importM").modal("hide");
		});
	}
});

$("#sessDestroy").click(function () {
	$.ajax({
		url      : '/' + mainController + "/resetsession",
		dataType : "script",
		type     : "POST",
		success  : function () {
			a_objects.removeAll();
			e_objects.removeAll();
		},
		error    : function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
});

$("#mapReset").click(function () {
	$.ajax({
		url      : '/' + mainController + "/resetsession",
		dataType : "script",
		type     : "POST",
		success  : function () {
			a_objects.removeAll();
			e_objects.removeAll();
			$("#mapSave, #ehashID, #SContainer").css('display', "block");
			// ({id:"void", maptype:"yandex#map", c:[40.56577018, 64.55124603], zoom:11, uhash:"MTAyZjI1OTAxNTg3", ehash:"MTAyZjI1OTAxNTg3", indb:1})
			// usermap = []; mp = { ehash:'NGQ2YjQwZmFhYzgx', uhash: 'M2ViZWZiNDEyOTRl', indb: 0 }
			map.setZoom(mp.zoom);
			map.setType(mp.maptype);
			$("#mapName").val(mp.ehash);
		},
		error   : function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
});

$("#linkClose").click(function () {
	$("#mapLinkContainer").addClass("hide");
});

$("#submitSelection").click(function () {
	var target = $("#location_id").val();
	e_objects.each(function(item) {
		if (item.properties.get("ttl") == target) {
			item.properties.set({ imageList : imageList })
		}
	});
	/* здесь должно быть заполнение поля объекта данными */
	$("#imageM").modal("hide");
});

ymaps.ready(setup_environment);

// Yet unused FX
// Frame Control
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
	$('.modal:has(.carousel)').on('shown', function () {
		var $carousel = $(this).find('.carousel');
		if ($carousel.data('carousel') && $carousel.data('carousel').sliding) {
			$carousel.find('.active').trigger($.support.transition.end);
		}
	});
}

function carousel_init() {
	$.ajax({
		url      : "/ajaxutils/getimagelist/",
		data     : {
			picref: $('#l_photo').attr('picref')
		},
		type     : "POST",
		cache    : false,
		dataType : "html",
		success  : function (data) {
			var newid = 'car_' + ($(".carousel").attr('id').split('_')[1] += 1);
			$("#pic_collection").empty().append(data);
			$(".carousel").attr('id', newid);
			$('#' + newid).carousel();
		},
		error    : function (data, stat, err) {
			console.log("При поиске изображений произошла ошибка на сервере");
			console.log([ data, stat, err ]);
		}
	});
}
*/