/* jshint -W100 */
/* jshint undef: true, unused: true */
/* globals ymaps, confirm, style_src, usermap, style_paths, yandex_styles, yandex_markers, style_circles, style_polygons, styleAddToStorage */

'use strict';

var userstyles,
	ttl,
	map,
	aObjects,
	eObjects,
	counter          = 0,
	apiURL          = '<?=$this->config->item("api");?>',
	baseURL         = '<?=$this->config->item("baseURL");?>',
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
		"Point"      : 1,
		"LineString" : 2,
		"Polygon"    : 3,
		"Circle"     : 4,
		"Rectangle"  : 5 //not used
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
	optionEdit       = { draggable: 1, zIndex: 300, zIndexActive: 300, zIndexDrag: 300, zIndexHover: 300 },
	optionIdle       = { draggable: 0, zIndex:   1, zIndexActive:   1, zIndexDrag:   1, zIndexHover:   1 };

function normalizeStyle(style, type) {
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

function listStyles() {
	var a,
		targets = {
			1 : "m_style",
			2 : "#line_style",
			3 : "#polygon_style",
			4 : "#circle_style",
			5 : "#rectangle_style"
		};
	$("#m_style").append('<optgroup label="Объекты">');
	for (a in yandex_styles + yandex_markers) {
		if (yandex_styles.hasOwnProperty(a)) {
			$(targets[1]).append(yandex_styles[a]);
		}
	}
	$("#m_style").append('</optgroup><optgroup label="Пользовательские">');
	for (a in userstyles) {
		if (userstyles.hasOwnProperty(a) && userstyles[a].type === 2) {
			$(targets[userstyles[a].type]).append('<option value="' + a + '">' + userstyles[a].title + '</option>');
		}
	}
	$("#m_style").append('</optgroup>');
}

function genListItem(item) {
	var ttl     = item.properties.get('ttl'),
		name    = item.properties.get('name'),
		address = item.properties.get('address'),
		pic     = gIcons[item.geometry.getType()];
	return '<div class="btn-group">' +
		'<button class="btn btn-mini mg-btn-list" ttl=' + ttl + '>' +
		'<img src="' + apiURL + '/images/' + pic + '" alt="">Название: ' + name + '<br>' +
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

function countObjects() {
	$("#ResultBody, #nowEdited").empty();
	aObjects.each(function (item) {
		$("#ResultBody").append(genListItem(item));
	});
	eObjects.each(function (item) {
		$("#nowEdited").append(genListItem(item));
	});
	$(".mg-btn-list").click(function () {
		var ttl = $(this).attr("ttl");
		aObjects.each(function (item) {
			if (item.properties.get("ttl") === ttl) {
				item.balloon.open(item.geometry.getCoordinates());
			}
		});
		eObjects.each(function (item) {
			if (item.properties.get("ttl") === ttl) {
				item.balloon.open(item.geometry.getCoordinates());
				openEditPane(item.geometry.getType());
			}
		});
	});
	eventListenersAdd();
}

function fromClipboard(src, wst) {
	/*
	вставка данных из локального буфера обмена
	*/
	var ttl = $(src).attr('ttl');
	function fromClipboard(item, wst) {
		if (ttl === item.properties.get('ttl')) {
			item.properties.set({
				name        : clipboard.name,
				address     : clipboard.address,
				description : clipboard.description,
				hintContent : clipboard.name + ' ' + clipboard.address
			});
			if (wst === 1 && item.geometry.getType() === clipboard.gtype) {
				item.options.set(ymaps.option.presetStorage.get(normalizeStyle(clipboard.preset, clipboard.gtype)));
				item.properties.set({ preset: clipboard.preset });
			}
		}
	}
	eObjects.each(function (item) {
		fromClipboard(item, wst);
	});
	aObjects.each(function (item) {
		fromClipboard(item, wst);
	});
	countObjects();
}

function toClipboard(src) {
	/*
	помещение данных в локальный буфер обмена
	*/
	var ttl = $(src).attr('ttl');
	function setClipboard() {
		if (ttl === item.properties.get('ttl')) {
			clipboard = {
				name        : item.properties.get('name'),
				address     : item.properties.get('address'),
				description : item.properties.get('description'),
				preset      : item.properties.get('preset'),
				gtype       : item.geometry.getType()
			};
		}
	}
	eObjects.each(function (item) {
		setClipboard(item);
	});
	aObjects.each(function (item) {
		setClipboard(item);
	});
	countObjects();
}

function lengthCalc(src) {
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

function perimeterCalc(src) {
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
	eObjects.each(function (item) {
		if (item.properties.get("ttl") === ttl) { // !!!!!!!!!!!!!!!!! === OR == ?
			eObjects.remove(item);
		}
	});
	aObjects.each(function (item) {
		if (item.properties.get("ttl") === ttl) {
			aObjects.remove(item);
		}
	});
	$.ajax({
		url: "/" + mainController + "/deleteobject",
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
	eObjects.each(function (item) {
		if (item.properties.get("ttl") === ttl) {
			item.properties.set({
				description : desc,
				address     : addr,
				name        : name,
				link        : link,
				hintContent : name + ' ' + addr
			});
			aObjects.add(item);
			item.options.set(optionIdle);
			sendObject(item);
		}
	});
	if (eObjects.getLength() === 1) {
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

function eventListenersAdd() {
	$(".balloonClose").unbind().click(function () {
		map.balloon.close();
	});

	if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
		$(".sw-edit").addClass("hide");
		return false;
	}

	function stopEdit() {
		nullTracers();
		counter = 0;
		map.balloon.close();
		countObjects();
	}

	$("#uploadDir").val(mp.uhash);

	$(".sw-finish").unbind().click(function () {
		doFinish(this);
		stopEdit();
	});

	$(".sw-edit").unbind().click(function () {
		doEdit(this);
		countObjects();
		counter = 1;
	});

	$(".sw-del").unbind().click(function () {
		doDelete(this);
		stopEdit();
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
				if (uploadresult.status.toString() === "1") {
					$("#uploadForm").addClass("hide");
					$("#mainForm").removeClass("hide");
				}
				if (uploadresult.status.toString() === "0") {
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
		type        : 'POST',
		data        : { uploadDir : mp.uhash },
		dataType    : 'script',
		success     : function(){
			var a;
			$("#imageList").empty();
			for ( a in imagesData) {
				if (imageList.hasOwnProperty(a)) {
					$("#imageList").append('<li file="' + imagesData[a].file + '"><img title="' + imagesData[a].file + '" src="/storage/' + mp.uhash + '/128/' + imagesData[a].file + '"></li>');
				}
			}
			for ( a in imageList ) {
				if (imageList.hasOwnProperty(a)) {
					$('#imageList li[file="' + imageList[a].split("/")[1] + '"]').addClass("active");
				}
			}
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
				$("#locationImages").empty();
				$("#imageList li.active").each(function() {
					imageList.push($(this).attr("file"));
					$("#locationImages").append('<img src="/storage/' + mp.uhash + '/32/'+ $(this).attr("file") +'">');
				});
				//console.log(imageList.toSource());
			});
		}
	});
	$("#imageM").modal("show");
}

function tracePoint(src) {
	var names = [],
		coords = src.geometry.getCoordinates(),
		cstyle = src.properties.get("attr");
	if ($("#traceAddress").prop('checked')) {
		runGeoCoding(coords).then(function(decAddr){
			src.properties.set({ hintContent: decAddr, address: decAddr });
		});
	}
	sendObject(src);
	countObjects();
	$("#m_lon").val(parseFloat(coords[0]).toFixed(precision));
	$("#m_lat").val(parseFloat(coords[1]).toFixed(precision));
	$("#m_style [value='" + cstyle + "']").attr("selected", "selected");
}

function tracePolyline(src) {
	var coords = src.geometry.getCoordinates(),
		cstyle = src.properties.get("attr");
	$("#line_vtx").html(src.geometry.getLength());
	$("#line_len").html(lengthCalc(coords) + " м.");
	$("#line_style [value='" + cstyle + "']").attr("selected", "selected");
}

function tracePolygon(src) {
	var coords = src.geometry.getCoordinates(),
		cstyle = src.properties.get("attr");
	$("#polygon_vtx").html(coords[0].length - 1);
	$("#polygon_len").html(perimeterCalc(coords) + " м.");
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
	var ttl = $(src).attr('ttl');
	$("#location_id").val(ttl); // здесь строка
	map.balloon.close();
		aObjects.each(function (item) {
			if (item.properties.get("ttl") === ttl) {
				var type = item.geometry.getType(); // получаем YM тип геометрии объекта
				eObjects.add(item); // переводим объект в группу редактируемых
				item.balloon.open(item.geometry.getCoordinates());
				imageList = ( usermap[ttl] === undefined || usermap[ttl].i === undefined ) ? [] : usermap[ttl].i;
			if (eObjects.getLength() > 1) { // нет особого смысла задавать вручную координаты точек, если их для редактирования выбрано больше чем одна. Отключаем поля
				$(".pointcoord, .circlecoord").prop('disabled', true);
			}
			if (type === "LineString" || type === "Polygon") {
				item.editor.startEditing();
			}
			item.options.set(optionEdit);
			openEditPane(type); // открываем требуемую панель редактора
			traceNode(item);
		}
	});
	eventListenersAdd();
}

function doFinishAll() {
	eObjects.each(function (item) {
		while (eObjects.getLength()) {
			aObjects.add(item); // эта операция не столько добавляет, сколько ПЕРЕМЕЩАЕТ объекты.
			item.options.set({ optionIdle });
		}
	});
	countObjects();
}

function lockCenter() {
	if (isCenterFixed) {
		$(".mapcoord").prop('disabled', true);
		$("#mapFix").html('Отслеживать центр').attr('title', 'Разрешить перемещать центр');
		return true;
	}
	$("#mapFix").attr('title', 'Не перемещать центр').html('Фиксировать центр');
	$(".mapcoord").prop('disabled', false);
}

function setPointCoordinates() {
	/* ручной ввод параметров геометрии точки из полей навигатора */
	eObjects.get(0).geometry.setCoordinates([parseFloat($("#m_lon").val()), parseFloat($("#m_lat").val())]);
}

function setCircleCoordinates() {
	/*
	ручной ввод параметров центра геометрии круга из полей навигатора
	*/
	var ttl = $('#location_id').val();
	eObjects.each(function (item) {
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
	eObjects.each(function (item) {
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

function getImageBySize(image, size) {
	var a,
		pathComponents,
		output = [],
		sizes  = {
			small   : '<img src="' + apiURL + '/images/nophoto.gif">',
			preview : '<img src="' + apiURL + '/images/nophoto.jpg">'
		};
	if (!image.length) {
		return [sizes[size]];
	}
	for ( a in image ) {
		if (image.hasOwnProperty(a)) {
			if (image[a].length) {
				output.push('<img src="/storage/' + mp.uhash + '/' + size +'/' + image[a] +'">');
			}
			if (!image[a].length) {
				output.push(sizes[size]);
			}
		}
	}
	return output;
}

function placeFreehandObjects(source) {
	var src,
		object,
		geometry,
		options,
		properties,
		b,
		a,
		frame,
		frm,
		fx = {
			1: function () {
				geometry = src.c.split(",");
				object   = new ymaps.Placemark(geometry, properties, options);
			},
			2: function () {
				geometry = new ymaps.geometry.LineString.fromEncodedCoordinates(src.c);
				object   = new ymaps.Polyline(geometry, properties, options);
			},
			3: function () {
				geometry = new ymaps.geometry.Polygon.fromEncodedCoordinates(src.c);
				object   = new ymaps.Polygon(geometry, properties, options);
			},
			4: function () {
				geometry = new ymaps.geometry.Circle([parseFloat(src.c.split(",")[0]), parseFloat(src.c.split(",")[1])], parseFloat(src.c.split(",")[2]));
				object   = new ymaps.Circle(geometry, properties, options);
			},
			5: function () {}
		};
	for (b in source) {
		if (source.hasOwnProperty(b)) {
			src     = source[b];
			options = ymaps.option.presetStorage.get(normalizeStyle(src.a, src.p));
			frame   = (src.frame === undefined) ? 1 : parseInt(src.frame, 10);
			properties = {
				attr        : src.a,
				description : src.d,
				address     : src.b,
				hintContent : src.n + ' ' + src.d,
				img         : getImageBySize(src.i, 'small')[0],
				img128      : getImageBySize(src.i, 'preview')[0],
				frame       : frm,
				link        : src.l,
				name        : src.n,
				ttl         : b.toString(),
				images      : getImageBySize(src.i, 'small').join(" ")
			};
			fx[src.p]();
			if (mframes[frame] === undefined) {
				mframes[frame] = new ymaps.GeoObjectArray();
			}
			mframes[frame].add(object);
		}
	}
	if(!mframes.length){
		mframes[1] = new ymaps.GeoObjectArray();
	}
	showFrame(mframes[1]);
	countObjects();
}

function runGeoCoding(coords) {
	var addressArray,
		decAddr,
		names = [];
	return ymaps.geocode(coords, { kind: ['house'] })
	.then(function (addressComponents) {
		addressComponents.geoObjects.each(function (object) {
			names.push(object.properties.get('name'));
		});
		addressArray = names[0];
		decAddr      = (addressArray === undefined || ![addressArray].join(', ').length) ? "Нет адреса" : [addressArray].join(', ');
		return decAddr;
	});
}

function displayLocations() {
	var a,
		cursor,
		object,
		layerTypes,
		dX = [],
		typeSelector,
		searchControl,
		viewPort,
		coords,
		forIFrame      = 0,
		objectGID      = parseInt($("#gCounter").val(), 10),
		mapCenter      = $("#mapCenter").val(),
		lon            = (isNaN(ymaps.geolocation.longitude)) ? parseFloat(mapCenter.toString().split(",")[0]) : ymaps.geolocation.longitude,
		lat            = (isNaN(ymaps.geolocation.latitude))  ? parseFloat(mapCenter.toString().split(",")[1]) : ymaps.geolocation.latitude,
		currentZoom    = ($("#current_zoom").val().length)    ? $("#current_zoom").val() : 15,
		tileServerID   = parseInt(Math.random() * (3 - 1) + 1, 10).toString(),
		tileServerLit  = { "0": "a", "1": "b", "2": "c", "3": "c", "4": "b", "5": "a" },
		genericBalloon = ymaps.templateLayoutFactory.createClass(
			'<div class="ymaps_balloon">' +
			'<div id="l_photo" data-toggle="modal" picref="$[properties.ttl|0]">' +
			'$[properties.img128|<img src="' + apiURL + '/images/nophoto.jpg">]' +
			'</div>' +
			'<div class="propertyContainer"><div class="property">Название:</div>&nbsp;$[properties.name|без имени]</div>' +
			'<div class="propertyContainer"><div class="property">Адрес:</div>&nbsp;$[properties.address|нет]</div>' +
			'<div class="propertyContainer"><div class="property">Описание:</div>&nbsp;$[properties.description|без описания]</div>' +
			'<div class="link"><a href="$[properties.link|#]" class="btn btn-block" target="_blank">Подробности здесь</a></div>' +
			'<div class="pull-right" style="margin-top:20px;">' +
			'<button type="button" class="btn btn-mini btn-primary sw-edit" ttl="$[properties.ttl|0]" style="margin-right:8px;">Редактировать </button>' +
			'<button type="button" class="btn btn-mini btn-info balloonClose">Закрыть</button>' +
			'</div></div>'
		),
		iframeBalloon = ymaps.templateLayoutFactory.createClass(
			'<div class="ymaps_balloon_iframed">' +
			'<iframe src="$[properties.link|]" width="400" height="400" style="border:none;margin:0;padding:0;"></iframe>' +
			'<div class="link"><a href="$[properties.link|#]" class="btn btn-mini btn-block" target="_blank">Подробности здесь</a></div>' +
			'<div class="pull-right" style="margin-top:20px;">' +
			'<button type="button" class="btn btn-mini btn-primary sw-edit" ttl="$[properties.ttl|0]">Редактировать </button>' +
			'<button type="button" class="btn btn-mini btn-info balloonClose">Закрыть</button>' +
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
			'$[properties.images|]' +
			'<div class="btn-group" style="float:right;margin-top: 4px;">' +
			'<button class="btn" type="button" id="imgSelector" title="Выбрать изображения"><i class="icon-picture"></i></button>' +
			'<button class="btn" type="button" id="imgUploader" title="Загрузить изображения"><i class="icon-upload"></i></button>' +
			'</div>' +
			'</span>' +
			'</label>' +
			'<label><textarea placeholder="Описание..." id="bal_desc" rows="6" cols="6">$[properties.description|нет]</textarea></label>' +
			'<div class="pull-right">' +
			'<button type="button" class="btn btn-mini btn-primary sw-finish" ttl="$[properties.ttl|0]">Готово</button>' +
			'<button type="button" class="btn btn-mini btn-danger sw-del" ttl="$[properties.ttl|0]">Удалить</button>' +
			'<button type="button" class="btn btn-mini btn-info balloonClose">Закрыть</button>' +
			'</div>' +
			'</div>' +
			'<div id="uploadForm" class="hide"><h4>Загрузить изображения<button type="button" id="toMain" class="btn pull-right" title="Вернуться к свойствам"><i class="icon-list" ></i></button></h4>' +
			'<form method="post" id="uForm" action="/upload/files">' +
			'<input type="hidden" name="uploadDir" id="uploadDir" value="' + mp.uhash + '">' +
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
		runGeoCoding(coords).then(function(decAddr){
			if (map.balloon.isOpen()) {
				$("#bal_addr").val(decAddr);
				return true;
			}
			map.balloon.open(coords, {
				contentBody: '<div class="ymaps_balloon row-fluid"><input type="text" value="' + [ coords[0].toPrecision(precision), coords[1].toPrecision(precision)].join(', ') + '"><br>' + decAddr + '</div>'
			});
		});
	}

	function applyPreset(src, style) {
		src.options.set(ymaps.option.presetStorage.get(style)); // назначение стиля в опции.
		src.properties.set({ attr: style }); // параллельная запись определения в свойства.
		sendObject(src);
	}

	function detectErrors(mp, prType, counter) {
		if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
			console.log("Рисование запрещено");
			return true;
		}
		if (prType === 0) {
			console.log("Ошибка в декодировании типа объекта. 0 не является допустимым типом");
			return true;
		}
		if (counter) {
			if (!confirm("На карте присутствуют редактируемые объекты.\nЗавершить их редактирование и создать новый объект?")) {
				return true;
			}
			doFinishAll();
			return false;
		}
		return false;
	}

	function drawObject(click) {
		var geometry,
			object,
			names      = [],
			coords     = click.get('coordPosition'),
			selectors  = {
				1 : '#m_style',
				2 : '#line_style',
				3 : '#polygon_style',
				4 : '#circle_style',
				5 : ''
			},
			prType     = geoType2IntId[$("#current_obj_type").val()],
			realStyle  = normalizeStyle($(selectors[prType]).val(), prType),
			options    = ymaps.option.presetStorage.get(realStyle),
			fx         = {
				1: function (click) {
					geometry = { type: "Point", coordinates: click.get('coordPosition') };
					object   = new ymaps.Placemark(geometry, properties, options);
					traceNode(object);
				},
				2: function (click) {
					geometry = { type: 'LineString', coordinates: [click.get('coordPosition')] };
					object   = new ymaps.Polyline(geometry, properties, options);
					sendObject(object);
				},
				3: function (click) {
					geometry = { type: 'Polygon', coordinates: [[click.get('coordPosition')]] };
					object   = new ymaps.Polygon(geometry, properties, options);
					sendObject(object);
				},
				4: function (click){
					geometry = [click.get('coordPosition'), $("#cir_radius").val()];
					object   = new ymaps.Circle(geometry, properties, options);
					traceNode(object);
				},
				5: function(){}
			},
			properties = {
				preset      : realStyle,
				attr        : realStyle,
				frame       : parseInt($('#vp_frame').val(), 10),
				ttl         : (objectGID += 1).toString(),
				name        : "",
				img         : "nophoto.gif",
				hintContent : '',
				address     : '',
				contact     : '',
				description : '',
				imageList   : []
			};

		if( detectErrors(mp, prType, counter) ) {
			return false;
		}

		fx[prType](click);

		// YET ANOTHER GEOCODE
		runGeoCoding(coords).then(function(decAddr){
			object.properties.set({ hintContent : decAddr, address : decAddr });
		});

		object.options.set( optionEdit );
		eObjects.add(object);
		if (prType === 2 || prType === 3) {
			object.editor.startDrawing();
		}
		counter += 1;
		$('#location_id').val(objectGID);
		countObjects();
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
				if (mp !== undefined) {
					if (mp.id === "void") {
						$("#mapSave, #ehashID, #SContainer").addClass("hide");
						$("#mapSave, #mapDelete").parent().addClass("hide");
						lockCenter();
					}
					if (mp.id !== "void") {
						$("#mapSave, #ehashID, #SContainer").removeClass("hide");
						$("#mapSave, #mapDelete").parent().removeClass("hide");
					}
					map.setType(mp.maptype).setZoom(mp.zoom).panTo(mp.c);
				}
				if (usermap.error !== undefined) {
					console.log(usermap.error);
					return false;
				}
				aObjects.removeAll();
				eObjects.removeAll();
				if (usermap.error === undefined) {
					placeFreehandObjects(usermap);
				}
				countObjects();
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
				placeFreehandObjects(usermap);
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
				aObjects.removeAll();
				eObjects.removeAll();
				placeFreehandObjects(usermap);
				countObjects();
				history.pushState("", "", "/map/" + mp.ehash);
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
		12: {
			func  : function () {return new ymaps.Layer(function (tile, zoom) {return "http://mt" + tileServerID + ".google.com/vt/lyrs=s&hl=ru&x=" + tile[0] + "&y=" + tile[1] + "&z=" + zoom + "&s=Galileo"; }, {tileTransparent: 1, zIndex: 1000}); },
			folder: "",
			label : "satellite#google",
			name  : "Аэрофотосъёмка, Google",
			layers: ["satellite#google"]
		}
	};
	aObjects    = new ymaps.GeoObjectArray();
	eObjects    = new ymaps.GeoObjectArray();
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
		center               : [lon, lat],//(mapCenter.length) ? [ parseFloat(mapCenter.split(",")[1]), parseFloat(mapCenter.split(",")[0]) ] : [lon, lat],
		zoom                 : currentZoom,
		//type                 : currentType,
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
		frame    : 1,
		vPCenter : [ map.getCenter()[0].toFixed(precision), map.getCenter()[1].toFixed(precision) ],
		zoom     : 13,
		cType    : 'yandex#satellite'
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

	aObjects.options.set({
		balloonContentBodyLayout: (forIFrame) ? 'iframe#balloonLayout' : 'generic#balloonLayout',
		balloonMaxWidth:  800,
		balloonMaxHeight: 800
	});

	eObjects.options.set({
		balloonContentBodyLayout: 'editing#balloonLayout',
		balloonWidth: 800
	});

	// ##### события #####
	// карта
	function setMapEvents() {
		map.events.add('balloonopen', function () {
			$('#upload_location').val($('#l_photo').attr('picref'));
			eventListenersAdd();
		});
		/*
		map.events.add('balloonclose', function () {
			//carousel_destroy();
		});
		*/
		map.events.add('boundschange', function (event) {
			if (isCenterFixed) {
				return false;
			}
			$("#vp_lon").val(event.get('newCenter')[0].toFixed(precision)); // сохраняем в поле новое значение центра карты
			$("#vp_lat").val(event.get('newCenter')[1].toFixed(precision)); // сохраняем в поле новое значение центра карты
			$("#map_center").val(event.get('newCenter').join(",")); // сохраняем в поле новое значение центра карты
			$("#current_zoom").val(event.get('newZoom')); // сохраняем в поле новое значение масштаба карты
			sendMap();
		});

		map.events.add('typechange', function () {
			$("#current_type").val(map.getType()); // сохраняем в поле новое значение типа карты
			sendMap();
		});

		map.events.add('click', function (event) {
			drawObject(event);
		});

		map.events.add('contextmenu', function (event) {
			showAddress(event);
		});
	}
	function setBackgroundEvents() {
		aObjects.events.add('contextmenu', function (e) {
			if (mp !== undefined && mp.id !== undefined && mp.id === 'void') {
				return false;
			}
			object = e.get('target');
			doEdit(object);
			countObjects();
			counter = 1;
		});

		aObjects.events.add('click', function (e) {
			var object = e.get('target');
			if (eObjects.getLength() !== 1) {
				return false;
			}
			eObjects.each(function (item) {
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
	}
	function setActiveEvents() {
		eObjects.events.add('dragend', function (e) {
			var object = e.get('target');
			traceNode(object);
		});
	}
	setActiveEvents();
	setMapEvents();
	setBackgroundEvents();
	// ###### конец описания событий

	map.geoObjects.add(aObjects);
	map.geoObjects.add(eObjects);
	//################################## выносные функции

	$("#m_style, #line_style, #polygon_style, #circle_style").change(function () {
		var val = $(this).val();
		eObjects.each(function (item) {
			applyPreset(item, val);
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

function setupEnvironment() {
	styleAddToStorage(userstyles);
	listStyles();
	displayLocations();
}

function hideFrame(frame) {
	/*
	функция переключения фрейма
	фреймы пока упразднены, с их наследием надо разобраться
	*/
	mframes[frame].removeAll();
	while (aObjects.get(0)) {
		mframes[frame].add(aObjects.get(0));
	}
	while (eObjects.get(0)) {
		mframes[frame].add(eObjects.get(0));
	}
}

function showFrame(frame) {
	/*
	функция отображения фрейма
	*/
	while (frame.get(0)) {
		aObjects.add(frame.get(0));
	}
}

function openLink(linkhash) {
	$("#mapLink").val(baseURL + 'map/' + linkhash);
	$("#mapLinkContainer").removeClass("hide");
}

function setMapItem (data) {
	var coords,
		initCoord = data[0].coord,
		pType     = geoType2IntId[data[0].type],
		reverse   = $("#cRev").prop("checked"),
		coordsFX  = {
			1 : function (coords, reverse) {
				return (reverse) ? coords.reverse().join(",") : coords.join(",");
			},
			2 : function (coords, reverse) {
				return coords;
			},
			3 : function (coords, reverse) {
				return coords;
			},
			4 : function (coords, reverse) {
				return ($("#cRev").prop("checked")) ? [ coords[1], coords[0], coords[2] ].join(",") : coords.join(",");
			},
			5 : function (coords, reverse) {
				return coords;
			}
		}
	coords = coordsFX[pType](initCoord, reverse);
	return {
		frame : 1,
		d     : data[1].d,
		n     : data[1].n,
		a     : data[2].attr,
		p     : pType,
		c     : coords,
		b     : data[1].b,
		l     : data[1].l,
		i     : ['']
	}
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
	hideFrame(pfrm);
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
					placeFreehandObjects(usermap);
				},
				error    : function (data, stat, err) {
					console.log([ data, stat, err ]);
				}
			});
		}
	}
	showFrame(nfrm);
	$("#vp_frame").val(nfrm);
});

$(".mapcoord").blur(function () {
	setMapCoordinates();
});

$("#mapFix").click(function () {
	isCenterFixed = (isCenterFixed) ? 0 : 1;
	lockCenter();
});

$("#linkFactory a").click(function (e) {

	var mode = parseInt($(this).attr('pr'), 10),
		fx = {
			1: function () {
				openLink(mp.ehash);
			},
			2: function () {
				openLink(mp.uhash);
			},
			3: function (e) {
				$.ajax({
					url      : "/" + expController + '/loadscript/' + mp.uhash,
					dataType : "html",
					type     : "POST",
					success  : function () {
						window.location.href = expController + '/loadscript/' + mp.uhash;
					},
					error    : function (data, stat, err) {
						console.log([ data, stat, err ]);
					}
				});
			},
			4: function (e) {
				$.ajax({
					url      : "/" + expController + "/createframe/" + mp.uhash,
					dataType : "script",
					type     : "POST",
					success  : function () {
						$("#mapLinkContainer").removeClass("hide");
						$("#mapLink").val(baseURL + expController + '/loadframe/' + mp.uhash);
					},
					error    : function (data, stat, err) {
						console.log([ data, stat, err ]);
					}
				});
			},
			5: function () {
				$.ajax({
					url      : "/" + expController + "/transfer",
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
			},
			6: function () {
				$("#importM").modal("show");
				$("#importBtn").unbind().click(function (){
					var a,
						pType,
						coords,
						exportedMapObjects;
					eval($("#importCode").val());
					for (a in exportedMapObjects) {
						if (exportedMapObjects.hasOwnProperty(a)) {
							usermap[a] = setMapItem(exportedMapObjects[a]);
						}
					}
					placeFreehandObjects(usermap);
					syncToSession(usermap);
					$("#importM").modal("hide");
				});
			}
		};
		e.preventDefault();
	if (mp === undefined) {
		console.log("Текущая карта ещё не была обработана.");
		return false;
	}
	fx[mode]();
	/*
		exportedMapObjects = {
			28: [{ type: "Point", coord: [40.59971845632609,64.48305691751406] }, { b: "улица Нахимова, 15", d: "Митинг Памяти у памятника портовикам, погибшим в годы Великой Отечественной войны, площадь у здания КЦ «Бакарица»", n: "7 мая 14:00", l: '' },{ attr: "twirl#redIcon" }]
		}
	*/
});

/*
$("#sessDestroy").click(function () {
	$.ajax({
		url      : '/' + mainController + "/resetsession",
		dataType : "script",
		type     : "POST",
		success  : function () {
			aObjects.removeAll();
			eObjects.removeAll();
		},
		error    : function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
});
*/

$("#mapReset").click(function () {
	resetSession();
});

$("#mapDelete").click(function () {
	mapDelete();
});

function resetSession() {
	$.ajax({
		url      : '/' + mainController + "/resetsession",
		dataType : "script",
		type     : "POST",
		success  : function () {
			aObjects.removeAll();
			eObjects.removeAll();
			$("#mapSave, #ehashID, #SContainer, #mapDelete").removeClass("hide");
			$("#mapSave, #mapDelete").parent().removeClass("hide");
			map.setZoom(mp.zoom);
			map.setType(mp.maptype);
			$("#mapName").val(mp.ehash);
			counter = 0;
			countObjects();
		},
		error   : function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
}

function mapDelete() {
	$.ajax({
		url      : '/mapmanager/deletemap',
		dataType : "text",
		data     : { hash: mp.uhash },
		type     : "POST",
		success  : function () {
			resetSession();
		},
		error   : function (data, stat, err) {
			console.log([ data, stat, err ]);
		}
	});
}

$("#linkClose").click(function () {
	$("#mapLinkContainer").addClass("hide");
});

$("#submitSelection").click(function () {
	var target = $("#location_id").val(),
		a;
	eObjects.each(function(item) {
		if (item.properties.get("ttl") === target) {
			item.properties.set({ imageList : imageList });
			usermap[target].i = imageList;
			//console.log(usermap[target].i);
		}
	});
	/* здесь должно быть заполнение поля объекта данными */
	$("#imageM").modal("hide");
});

ymaps.ready(setupEnvironment);

