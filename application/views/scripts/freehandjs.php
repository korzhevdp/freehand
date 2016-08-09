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
	apiURL           = '<?=$this->config->item("api");?>',
	baseURL          = '<?=$this->config->item("base_url");?>',
	mainController   = 'freehand',
	expController    = 'exports',
	objectGID        = parseInt($("#gCounter").val(), 10),
	forIFrame        = 0,
	traceAllowed     = false,
	imageList        = [],
	availableLayers  = {},
	timeWarp,
	mp               = {},
	sizesImg = {
		small   : '32',
		preview : '128',
		full    : '600'
	},
	sizesNoImg  = {
		small   : '<img src="' + apiURL + '/images/nophoto.gif">',
		preview : '<img src="' + apiURL + '/images/nophoto.jpg">'
	},
	clipboard        = { name: '', description: '', address: '', preset: '', gtype: "Point" },
	gIcons = {
		1 : 'marker.png',
		2 : 'layer-shape-polyline.png',
		3 : 'layer-shape-polygon.png',
		4 : 'layer-shape-ellipse.png',
		5 : "rectangle.png"
	},
	geoType2IntId    = {
		"Point"      : 1,
		"LineString" : 2,
		"Polygon"    : 3,
		"Circle"     : 4,
		"Rectangle"  : 5 //not used
	},
	mframes          = [],
	frame            = 1,
	precision        = 8,
	metricPrecision  = 2,
	isCenterFixed    = 0,
	optionEdit       = { draggable: 1, zIndex: 300, zIndexActive: 300, zIndexDrag: 300, zIndexHover: 300 },
	optionIdle       = { draggable: 0, zIndex:   1, zIndexActive:   1, zIndexDrag:   1, zIndexHover:   1 },
	optionBgArray    = { balloonContentBodyLayout: (forIFrame) ? 'iframe#balloonLayout' : 'generic#balloonLayout', balloonMaxWidth: 800, balloonMaxHeight: 800 },
	optionActArray   = { balloonContentBodyLayout: 'editing#balloonLayout', balloonWidth: 800 },
	tileServerID     = parseInt(Math.random() * (3 - 1) + 1, 10).toString(),
	tileServerLit    = { "0": "a", "1": "b", "2": "c", "3": "c", "4": "b", "5": "a" },
	layerTypes       = {
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

function init() {
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
			style = ["twirl", style.split("#")[1]].join("#"); // требуется разбор на версии 2 и 2.1 
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
				2 : "line_style",
				3 : "polygon_style",
				4 : "circle_style",
				5 : "rectangle_style"
			};
		$("#m_style").append('<optgroup label="Объекты">');
		for (a in yandex_styles + yandex_markers) {
			if (yandex_styles.hasOwnProperty(a)) {
				$("#" + targets[1]).append(yandex_styles[a]);
			}
		}
		$("#m_style").append('</optgroup><optgroup label="Пользовательские">');
		for (a in userstyles) {
			if (userstyles.hasOwnProperty(a)) {
				$("#" + targets[userstyles[a].type]).append('<option value="' + a + '">' + userstyles[a].title + '</option>');
			}
		}
		$("#m_style").append('</optgroup>');
	}

	function genListItem(item) {
		var ttl     = item.properties.get('ttl'),
			name    = item.properties.get('name'),
			addr = item.properties.get('addr'),
			pic     = gIcons[geoType2IntId[item.geometry.getType()]];
		return '<div class="btn-group">' +
			'<button class="btn btn-mini mg-btn-list" ttl=' + ttl + '>' +
			'<img src="' + apiURL + '/images/' + pic + '" alt="">Название: ' + name + '<br>' +
			'Адрес: ' + addr +
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
		$("#current_obj_type").val(type);
		$(".obj_sw, #navheader li, #results").removeClass('active');
		$(".obj_sw[pr=" + type + "], #palette, #mainselector").addClass('active');
		$(".navigator-pane").addClass("hide");
		$("#navigator-pane" + type).removeClass("hide");
	}

	function countObjects() {
		$("#ResultBody, #nowEdited").empty();
		aObjects.each(function (item) {
			$("#ResultBody").append(genListItem(item));
		});
		eObjects.each(function (item) {
			$("#nowEdited").append(genListItem(item));
		});
		$(".mg-btn-list").unbind().click(function () {
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
		clipboardInit();
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
					addr        : clipboard.addr,
					desc        : clipboard.desc,
					hintContent : clipboard.name + ' ' + clipboard.addr
				});
				if (wst === 1 && item.geometry.getType() === clipboard.gtype) {
					item.options.set(ymaps.option.presetStorage.get(normalizeStyle(clipboard.preset, clipboard.gtype)));
					item.properties.set({ attr: clipboard.attr });
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
					addr        : item.properties.get('addr'),
					desc        : item.properties.get('desc'),
					attr        : item.properties.get('attr'),
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

	function deleteItemByTtl(collection, ttl) {
		collection.each(function(item) {
			if (item.properties.get("ttl") === ttl) { // !!!!!!!!!!!!!!!!! === OR == ?
				collection.remove(item);
			}
		});
	}

	function makeFrameSelectorList() {
		var a;
		for ( a in usermap ) {
			if ( usermap.hasOwnProperty(a) ) {
				$("#frameSelectorList").append('<option value="' + usermap[a].frame + '">' + usermap[a].name + '</option>"');
			}
		}
	}

	function doDelete(src) {
		var ttl = $(src).attr('ttl');
		deleteItemByTtl(eObjects, ttl);
		deleteItemByTtl(aObjects, ttl);
		$.ajax({
			url       : "/" + mainController + "/deleteobject",
			data      : {
				ttl   : [ $(src).attr('ttl'), frame ].join("_"),
			},
			type: "POST",
			success   : function () {
				aObjects.options.set({ hasBalloon: 1 });
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
		if (mp.mode !== undefined && mp.mode === 'view') {
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
				desc     : item.properties.get('desc'),
				addr     : item.properties.get('addr'),
				link     : item.properties.get('link'),
				name     : item.properties.get('name'),
				img      : item.properties.get('imageList'),
				frame    : frame
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
		map.balloon.close();
		eObjects.each(function (item) {
			if (item.properties.get("ttl") === ttl) {
				item.properties.set({
					desc        : desc,
					addr        : addr,
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
		aObjects.options.set({ hasBalloon: 1 });
		clipboardInit();
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

		$("#l_photo").click(function() {
			var ref = $(this).attr("picref"),
				imageSet = usermap[frame].objects[ref].img,
				i = 0;
			function coalesceLocImages(filename){
				if (filename.length) {
					$("#locImg").attr("src", "/storage/600/" + filename);
				}
				if (!filename.length) {
					$("#locImg").attr("src", "http://api.arhcity.ru/images/nophoto.jpg");
				}
			}
			coalesceLocImages(imageSet[i]);
			if (imageSet.length) {
				i = 1;
				/*
				function cycleImages(){
					i += 1;
					if (i >= imageSet.length) {
						i = 0;
					}
					timeWarp = setInterval(cycleImages, 5000);
				}
				cycleImages();
				*/

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

		if (mp !== undefined && mp.mode !== undefined && mp.mode === 'view') {
			$(".sw-edit").addClass("hide");
			return false;
		}

		function stopEdit() {
			nullTracers();
			counter = 0;
			countObjects();
		}

		$(".sw-edit").unbind().click(function () {
			doEdit(this);
		});

		$(".sw-finish").unbind().click(function () {
			doFinish(this);
			stopEdit();
		});

		$(".sw-del").unbind().click(function () {
			doDelete(this);
			stopEdit();
		});


		$("#imgUploader").unbind().click(function () {
			$("#uploadForm").removeClass("hide");
			$("#mainForm").addClass("hide");
			$("#uploadDir").val(mp.uhash);
		});

		$("#toMain").unbind().click(function () {
			$("#mainForm").removeClass("hide");
			$("#uploadForm").addClass("hide");
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

		$("#imgSelector").unbind().click(function(){
			showImageSelector();
		})
	}

	function clipboardInit() {
		$(".copyProp").unbind().click(function () {
			toClipboard(this);
		});

		$(".pasteProp").unbind().click(function () {
			fromClipboard(this, 0);
		});

		$(".pastePropOpt").unbind().click(function () {
			fromClipboard(this, 1);
		});
	}

	function showImageSelector() {
		$.ajax({
			url         : '/mapmanager/listuserimages',
			type        : 'POST',
			data        : { uploadDir : mp.uhash },
			dataType    : 'script',
			success     : function() {
				var a,
					filename;
				$("#imageList").empty();
				if (imagesData === undefined) {
					return false;
				}
				for ( a in imagesData) {
					if (imagesData.hasOwnProperty(a)) {
						filename = imagesData[a].file.split("/")[1];
						$("#imageList").append('<li file="' + imagesData[a].file + '"><img title="' + imagesData[a].file + '" src="/storage/128/' + imagesData[a].file + '"><div>' + filename + '</div></li>');
					}
				}
				for ( a in imageList ) {
					if (imageList.hasOwnProperty(a)) {
						$('#imageList li[file="' + imageList[a] + '"]').addClass("active");
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
						$("#locationImages").append('<img src="/storage/32/' + $(this).attr("file") + '">');
					});
					//console.log(imageList.toSource());
				});
			}
		});
		$("#imageM").modal("show");
	}

	function insertGeoCodingProperty(object) {
		var coords = object.geometry.getCoordinates();
		runGeoCoding(coords).then(function(decAddr) {
			object.properties.set({ addr: decAddr, hintContent: decAddr });
			//eventListenersAdd();
		});
	}

	function tracePoint(object) {
		var coords = object.geometry.getCoordinates(),
			cstyle = object.properties.get("attr");
		insertGeoCodingProperty(object);
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
		var type   = geoType2IntId[src.geometry.getType()],
			fx     = {
				1: function (src) { tracePoint(src); },
				2: function (src) { tracePolyline(src); },
				3: function (src) { tracePolygon(src); },
				4: function (src) { traceCircle(src); }
			};
		fx[type](src);
		countObjects();
	}

	function disableMultiplePointCoordsInput() {
		if (eObjects.getLength() > 1) {
			$(".pointcoord, .circlecoord").prop('disabled', true);
		}
	}

	function doEdit(src) {
		var ttl = $(src).attr('ttl');
		counter = 1;
		$("#location_id").val(ttl); // здесь строка

		aObjects.each(function (item) {
			if (item.properties.get("ttl") === ttl) {
				var type = geoType2IntId[item.geometry.getType()];		// получаем YM тип геометрии объекта
				//map.balloon.close();
				eObjects.add(item);										// переводим объект в группу редактируемых
				item.balloon.open(item.geometry.getCoordinates());

				imageList = ( usermap[frame].objects.ttl === undefined || usermap[frame].objects.ttl.img === undefined ) ? [] : usermap[frame].objects.ttl.img;
				// нет особого смысла задавать вручную координаты точек, если их для редактирования выбрано больше чем одна. Отключаем поля
				disableMultiplePointCoordsInput();
				if (type === 2 || type === 3) {
					item.editor.startEditing();
					aObjects.options.set({ hasBalloon: 0 });
				}
				item.options.set(optionEdit);
				// открываем требуемую панель редактора
				openEditPane(type);
				traceNode(item);
			}
		});
		clipboardInit();
	}

	function doFinishAll() {
		eObjects.each(function (item) {
			while (eObjects.getLength()) {
				aObjects.add(item); // эта операция не столько добавляет, сколько ПЕРЕМЕЩАЕТ объекты.
				item.options.set({ optionIdle });
			}
		});
		clipboardInit();
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
			output  = [];
		if (!image.length) {
			return [sizesNoImg[size]];
		}
		for ( a in image ) {
			if (image.hasOwnProperty(a)) {
				if (image[a].length) {
					output.push('<img src="/storage/' + sizesImg[size] +'/' + image[a] +'">');
				}
				if (!image[a].length) {
					output.push(sizesNoImg[size]);
				}
			}
		}
		//alert(output.toSource());
		return output;
	}

	function processFrames(source){
		var frm;
		if (!mframes.length) {
			mframes[1] = new ymaps.GeoObjectArray();
		}
		for (frm in source) {
			if (source.hasOwnProperty(frm)) {
				processFrame(frm, source[frm].objects);
			}
		}
	}

	function processFrame(frameID, source) {
		var a,
			src,
			object,
			geometry,
			options,
			properties,
			localframe,
			entity,
			frm,
			fx = {
				0: function () {},
				1: function () {
					geometry = [ parseFloat(src.coords.split(",")[0]), parseFloat(src.coords.split(",")[1] ) ];
					object   = new ymaps.Placemark(geometry, properties, options);
				},
				2: function () {
					geometry = new ymaps.geometry.LineString.fromEncodedCoordinates(src.coords);
					object   = new ymaps.Polyline(geometry, properties, options);
				},
				3: function () {
					geometry = new ymaps.geometry.Polygon.fromEncodedCoordinates(src.coords);
					object   = new ymaps.Polygon(geometry, properties, options);
				},
				4: function () {
					geometry = new ymaps.geometry.Circle([parseFloat(src.coords.split(",")[0]), parseFloat(src.coords.split(",")[1])], parseFloat(src.coords.split(",")[2]));
					object   = new ymaps.Circle(geometry, properties, options);
				},
				5: function () {}
			};
		for (entity in source) {
			if (source.hasOwnProperty(entity)) {
				src     = source[entity];
				if (src.attr !== undefined) { //костыль. Причём непонятный
					options = ymaps.option.presetStorage.get(normalizeStyle(src.attr, src.type));
				}
				properties = {
					attr        : src.attr,
					desc        : src.desc,
					addr        : src.addr,
					hintContent : src.name + ' ' + src.desc,
					img         : getImageBySize(src.img, 'small')[0],
					img128      : getImageBySize(src.img, 'preview')[0],
					frame       : frameID,
					link        : src.link,
					name        : src.name,
					imageList   : src.img,
					ttl         : entity.toString(),
					images      : getImageBySize(src.img, 'small').join(" ")
				};
				fx[src.type]();
				//console.log(mframes.toSource());
				if (mframes[frameID] === undefined) {
					mframes[frameID] = new ymaps.GeoObjectArray();
				}
				mframes[frameID].add(object);
			}
		}
		for (a in usermap ) {
			if (usermap.hasOwnProperty(a) && mframes[a] === undefined) {
				mframes[a] = new ymaps.GeoObjectArray();
			}
		}
	}

	function placeFreehandObjects(source) {
		processFrames(source);
		showFrame(frame);
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

	function setMapControls(state) {
		if (state === "view") {
			$("#mapSave, #ehashID, #SContainer").addClass("hide");
			$("#mapSave, #mapDelete").parent().addClass("hide");
			lockCenter();
			return true;
		}
		$("#mapSave, #ehashID").removeClass("hide");
		$("#mapSave, #mapDelete").parent().removeClass("hide");
		$("#SContainer").css('top', mp.nav[0]).css('left', mp.nav[1]).removeClass("hide");
	}

	function setupMapFromProperties() {
		var mapType = ( availableLayers[mp.maptype] !== undefined ) ? mp.maptype : "yandex#map";
		$("#headTitle").html(mp.name);
		setMapControls(mp.mode);
		map.setType(mapType).setZoom(mp.zoom).panTo(mp.center);
		if(mp.nav[0] !== undefined ){
			$("#SContainer").css('top', mp.nav[0]).css('left', mp.nav[1]);
		}
	}

	function loadmap(name) { // загрузка строго из базы данных
		if (!name.length) {
			$("#mapName").val("Введите идентификатор карты").css('color', 'red');
			setTimeout(function(){ $("#mapName").val("").css('color', 'black') }, 2000);
			return false;
		}
		traceAllowed = false;
		$.ajax({
			url      : '/' + mainController + "/loadmap",
			type     : "POST",
			data     : {
				name : name
			},
			dataType : "script",
			success  : function () {
				if (mp !== undefined) {
					setupMapFromProperties();
				}
				if (usermap.error !== undefined) {
					console.log(usermap.error);
					return false;
				}
				aObjects.removeAll();
				eObjects.removeAll();
				if (usermap.error === undefined) {
					placeFreehandObjects(usermap);
					makeFrameSelectorList();
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
		traceAllowed = false;
		$.ajax({
			url      : '/' + mainController + "/getsession",
			dataType : "script",
			type     : "POST",
			success  : function () {
				var mapType;
				if (mp.state === "database") {
					loadmap($("#maphash").val());
					return true;
				}
				if (mp !== undefined) {
					setupMapFromProperties();
				}
				placeFreehandObjects(usermap);
				makeFrameSelectorList();
				/*
				адский костыль на отключение первичных отправок карты на сервер. :)
				включение отправки после задержки в 5 секунд с момента загрузки сессии.
				*/
				setTimeout(function() { traceAllowed = true }, 5000);
			},
			error: function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}

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
				history.pushState( "", "", "/map/" + mp.ehash );
			},
			error    : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	}

	function sendMap() {
		if ((mp !== undefined && mp.mode !== undefined && mp.mode === 'view')) {
			return false;
		}
		$.ajax({
			url     : '/' + mainController + "/savemap",
			type    : "POST",
			data    : {
				maptype : map.getType(),
				center  : [ $("#vp_lon").val(), $("#vp_lat").val() ],
				zoom    : map.getZoom(),
				nav     : mp.nav
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

	function detectErrors(mp, prType, counter) {
		if (mp !== undefined && mp.mode !== undefined && mp.mode === 'view') {
			console.log("Рисование запрещено");
			return true;
		}
		if (prType === 0) {
			console.log("Ошибка в декодировании типа объекта. 0 не является допустимым типом");
			return true;
		}
		if (counter) {
			if (confirm("На карте присутствуют редактируемые объекты.\nЗавершить их редактирование и создать новый объект?")) {
				doFinishAll();
				return false;
			}
			return true;
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
			prType     = $("#current_obj_type").val(),
			realStyle  = normalizeStyle($(selectors[prType]).val(), prType),
			options    = ymaps.option.presetStorage.get(realStyle),
			fx         = {
				1: function (click) {
					geometry = { type: "Point", coordinates: click.get('coordPosition') };
					object   = new ymaps.Placemark(geometry, properties, options);
					traceNode(object);
					sendObject(object);
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
					sendObject(object);
				},
				5: function(){}
			},
			properties = {
				preset      : realStyle,
				attr        : realStyle,
				frame       : frame,
				ttl         : (objectGID += 1).toString(),
				name        : "",
				img         : "nophoto.gif",
				hintContent : '',
				addr        : '',
				link        : '#',
				desc        : '',
				imageList   : []
			};

		if( detectErrors(mp, prType, counter) ) {
			return false;
		}

		fx[prType](click);

		// YET ANOTHER GEOCODE
		insertGeoCodingProperty(object);

		object.options.set( optionEdit );
		eObjects.add(object);
		if (prType === "2" || prType === "3") {
			object.editor.startDrawing();
			aObjects.options.set({ hasBalloon: 0 });
		}
		counter += 1;
		$('#location_id').val(objectGID);
		countObjects();
	}

	function fillMapForms() {
		$("#frameNum").val(frame);
		$("#vp_lon").val(map.getCenter()[0].toFixed(precision));
		$("#vp_lat").val(map.getCenter()[1].toFixed(precision));
		$("#current_obj_type").val(1);
	}

	function filterTypeSelector(selector){
		selector.removeMapType('yandex#publicMapHybrid');
		selector.removeMapType('yandex#hybrid');
		selector.removeMapType('yandex#publicMap');
	}

	function getLongitude() {
		var lon = (isNaN(ymaps.geolocation.longitude)) ? mp.center[0] : ymaps.geolocation.longitude;
		return lon;
	}

	function getLatitude() {
		var lat = (isNaN(ymaps.geolocation.latitude))  ? mp.center[1] : ymaps.geolocation.latitude;
		return lat;
	}

	function displayLocations() {
		var a,
			cursor,
			object,
			dX = [],
			typeSelector,
			searchControl,
			viewPort,
			coords,
			lon            = getLongitude(),
			lat            = getLatitude(),
			currentZoom    = ($("#current_zoom").val().length)    ? $("#current_zoom").val() : 15,
			genericBalloon = ymaps.templateLayoutFactory.createClass(
				'<div class="ymaps_balloon">' +
				'<div id="l_photo" data-toggle="modal" picref="$[properties.ttl|0]">' +
				'$[properties.img128|<img src="' + apiURL + '/images/nophoto.jpg">]' +
				'</div>' +
				'<div class="propertyContainer"><div class="property">Название:</div>&nbsp;$[properties.name|без имени]</div>' +
				'<div class="propertyContainer"><div class="property">Адрес:</div>&nbsp;$[properties.addr|нет]</div>' +
				'<div class="propertyContainer"><div class="property">Описание:</div>&nbsp;$[properties.desc|без описания]</div>' +
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
				'<label>Адрес:<input type="text" id="bal_addr" placeholder="Правый щелчок по карте добавит адрес места" value="$[properties.addr|нет]"></label>' +
				'<label>Ссылка:' +
				'<input type="text" id="bal_link" placeholder="Ссылка на web-страницу или изображение" value="$[properties.link|#]">' +
				'</label>' +
				'<label for="a2232G">Фото:' +
				'<span id="locationImages">' +
				'$[properties.images|]' +
				'</span>' +
				'<div class="btn-group" style="float:right;margin-top: 4px;">' +
				'<button class="btn" type="button" id="imgSelector" title="Выбрать изображения"><i class="icon-picture"></i></button>' +
				'<button class="btn" type="button" id="imgUploader" title="Загрузить изображения"><i class="icon-upload"></i></button>' +
				'</div>' +
				'</label>' +
				'<label><textarea placeholder="Описание..." id="bal_desc" rows="6" cols="6">$[properties.desc|нет]</textarea></label>' +
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

		//определение механизма пересчёта стандартной сетки тайлов в сетку тайлов Яндекс-карт (TMS)
		for (a = 0; a < 21; a += 1) {
			dX[a] = Math.pow(2, a) - 1;
		}
		aObjects     = new ymaps.GeoObjectArray();
		eObjects     = new ymaps.GeoObjectArray();
		typeSelector = new ymaps.control.TypeSelector();
		//ex_objects = new ymaps.GeoObjectArray(), //--B2

		function setupMapLayers(layers) {
			var a;
			// создаём слои наложения для карты
			for (a in layerTypes) {
				if (layerTypes.hasOwnProperty(a)) {
					ymaps.layer.storage.add(layerTypes[a].label, layerTypes[a].func);
					ymaps.mapType.storage.add(layerTypes[a].label, new ymaps.MapType(layerTypes[a].name, layerTypes[a].layers));
					typeSelector.addMapType(layerTypes[a].label, a);
					availableLayers[layerTypes[a].label] = 1;
				}
			}
		}
		setupMapLayers(layerTypes);

		map = new ymaps.Map("YMapsID", {
			center               : [lon, lat],//(mapCenter.length) ? [ parseFloat(mapCenter.split(",")[1]), parseFloat(mapCenter.split(",")[0]) ] : [lon, lat],
			zoom                 : currentZoom,
			//type                 : currentType,
			type                 : 'yandex#map',
			behaviors            : ["scrollZoom", "drag", "dblClickZoom", "multiTouch"]
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
		searchControl = new ymaps.control.SearchControl({ provider: 'yandex#publicMap'});
		map.controls.add('zoomControl').add(typeSelector).add('mapTools').add(searchControl);

		/* ViewPort data fields */
		viewPort = {
			frame    : 1,
			vPCenter : [ map.getCenter()[0].toFixed(precision), map.getCenter()[1].toFixed(precision) ],
			zoom     : 13,
			cType    : 'yandex#satellite'
		};

		fillMapForms();
		// ##### настройка представления карты #####
		filterTypeSelector(typeSelector);
		//$(".ymaps-b-form-input__input").empty().attr("placeholder", ymaps.geolocation.city);

		ymaps.layout.storage.add('generic#balloonLayout', genericBalloon);
		ymaps.layout.storage.add('editing#balloonLayout', editBalloon);
		ymaps.layout.storage.add('iframe#balloonLayout',  iframeBalloon);
		//ymaps.layout.storage.add('editingx#balloonLayout', editxBalloon); //--B2

		aObjects.options.set(optionBgArray);
		eObjects.options.set(optionActArray);

		// ##### события #####
		function setMapEvents() {
			map.events.add('balloonopen', function () {
				setTimeout(eventListenersAdd, 200); // костыль
			});
			/*
			map.events.add('balloonclose', function () {
				//carousel_destroy();
			});
			*/
			map.events.add('boundschange', function (event) {
				if (isCenterFixed || !traceAllowed) {
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
				if (mp !== undefined && mp.mode !== undefined && mp.mode === 'view') {
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
					var auxGeometry,
						id,
						type = geoType2IntId[item.geometry.getType()];
					if (type === 2) {
						item.geometry.insert(0 , object.geometry.getCoordinates());
					}
					if (type === 3) {
						auxGeometry = item.geometry.getCoordinates();
						auxGeometry[0][0] = object.geometry.getCoordinates();
						item.geometry.setCoordinates(auxGeometry);
					}
				});
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
		// поисковая форма
		$("#searchFormToggle").click(function () {
			var a = map.controls.indexOf(searchControl);
			if (a === (-1)) {
				map.controls.add(searchControl);
				$(this).css('opacity', 1);
				return false
			}
			map.controls.remove(searchControl);
			$(this).css('opacity', .5);
		});

		map.geoObjects.add(aObjects);
		map.geoObjects.add(eObjects);
		//################################## выносные функции
	}

	function setupEnvironment() {
		styleAddToStorage(userstyles);
		listStyles();
		loadSessionData();
		displayLocations();
	}

	function hideFrame(frame) {
		/*
		функция переключения фрейма
		фреймы пока упразднены, с их наследием надо разобраться
		*/
		if (mframes[frame] === undefined) {
			return false;
		}
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
		if ( mframes[frame] !== undefined) {
			while (mframes[frame].get(0)) {
				aObjects.add(mframes[frame].get(0));
			}
		}
		//console.log(frame, usermap[frame].name, aObjects.get(0).properties.get('attr'))
		if ( usermap !== undefined && usermap[frame] !== undefined && usermap[frame].name !== undefined ) {
			$("#uiFrameName").empty().html(usermap[frame].name);
		}
		countObjects();
		$("#frameActionM").modal('hide');
		$("#frameNum").val(frame);
	}

	function showFrameActionSelector() {
		$("#frameActionM").modal('show');
	}

	$("#submitFrameAction").click(function(){
		var mode   = parseInt($(".frameAction:checked").val(), 10), // 0 при создании пустого фрейма, 1 при клонировании.
			name   = $("#newFrameName").val(),
			frameL = $("#frameSelectorList").val();
		$.ajax({
			url       : '/' + mainController + '/writenewframe',
			data      : {
				name  : name,
				frame : frameL,
				clone : mode
			},
			dataType  : "script",
			type      : "POST",
			success   : function () {
				placeFreehandObjects(usermap);
			},
			error     : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	});

	function switchFrame(referer) {
		var newFrame;
		hideFrame(frame);
		newFrame = frame + parseInt(referer, 10);
		newFrame = (newFrame > 1) ? newFrame : 1;
		if (mframes[newFrame] === undefined) {
			if (mp.mode === "view") {
				showFrame(frame);
				return false;
			}
			showFrameActionSelector();
			return false;
		}
		frame = newFrame;
		showFrame(frame);
	}

	function openLink(linkhash) {
		$("#mapLink").val(baseURL + 'map/' + linkhash);
		$("#mapLinkContainer").removeClass("hide");
	}

	function setMapItem (data) {
		var coords,
			initCoord = data.coords,
			pType     = data.type,
			reverse   = $("#cRev").prop("checked"),
			coordsFX  = {
				1 : function (coords, reverse) {
					coords = coords.split(",");
					return (reverse) ? coords.reverse().join(",") : coords.join(",");
				},
				2 : function (coords, reverse) {
					return coords;
				},
				3 : function (coords, reverse) {
					return coords;
				},
				4 : function (coords, reverse) {
					coords = coords.split(",");
					return ($("#cRev").prop("checked")) ? [ coords[1], coords[0], coords[2] ].join(",") : coords.join(",");
				},
				5 : function (coords, reverse) {
					return coords;
				}
			}
		coords = coordsFX[pType](initCoord, reverse);
		return {
			frame    : frame,
			desc     : data.desc,
			name     : data.name,
			attr     : data.attr,
			type     : pType,
			coords   : coords,
			addr     : data.addr,
			link     : data.link,
			img      : ['']
		}
	}

	function syncToSession(usermap) {
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
				map.setType(mp.maptype).setZoom(mp.zoom).panTo(mp.center);
				$("#mapName").val(mp.ehash);
				history.pushState("", "", "/map/" + mp.ehash);
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

	$("#cancelNewFrame").click(function(){
		frame -= 1;
		showFrame(frame);
		console.log(frame)
	});

	$("#m_style, #line_style, #polygon_style, #circle_style").change(function () {
		var val = $(this).val();
		eObjects.each(function (item) {
			applyPreset(item, val);
		});
	});

	$(".frameSwitcher").click(function() {
		switchFrame($(this).attr("ref"));
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

	// события не-карты
	$(".obj_sw").click(function () {
		$("#current_obj_type").val($(this).attr('pr'));
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

	$(".mapcoord").blur(function () {
		setMapCoordinates();
	});

	$("#mapFix").click(function () {
		isCenterFixed = (isCenterFixed) ? 0 : 1;
		lockCenter();
	});

	$("#linkFactory a").click(function (event) {
		var mode = $(this).attr('pr'),
			fx = {
				1: function () {
					openLink(mp.ehash);
				},
				2: function () {
					openLink(mp.uhash);
				},
				3: function (event) {
					$.ajax({
						url      : "/" + expController + '/loadscript/' + mp.uhash,
						dataType : "html",
						type     : "POST",
						success  : function () {
							window.location.href = "/" + expController + '/loadscript/' + mp.uhash;
						},
						error    : function (data, stat, err) {
							console.log([ data, stat, err ]);
						}
					});
				},
				4: function (event) {
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
								usermap[frame].objects[a] = setMapItem(exportedMapObjects[a]);
							}
						}
						placeFreehandObjects(usermap);
						syncToSession(usermap);
						$("#importM").modal("hide");
					});
				}
			};
			event.preventDefault();
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

	$("#mapNew").click(function (){
		$("#newMapM").modal('show');
	});

	$("#mapRearrange").click(function (){
		var a,
			x,
			out = {},
			arr = $(".sortable").sortable("toArray", { attribute: 'framenum' });
		for ( a in arr ) {
			if (arr.hasOwnProperty(a)) {
				x = parseInt(arr[a], 10);
				out[x] = { name: usermap[x].name, order: (parseInt(a, 10) + 1) };
			}
		}
		$.ajax({
			url      : "/mapmanager/rearrangeframes",
			data     : {
				order : out,
			},
			dataType : "text",
			type     : "POST",
			success  : function (data) {
				window.location.reload();
				$("#mapPropertiesM").modal("hide");
			},
			error    : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	});


	$("#propertiesShow").click(function (){
		$.ajax({
			url      : "/mapmanager/getproperties",
			data     : {
				hash : mp.uhash
			},
			dataType : "script",
			type     : "POST",
			success  : function () {
				$( "#frameList" ).html(data.frameList);
				$( "#mapNameProperty" ).val(data.mapNameProperty);
				$("#frameName").val('');
				$( ".sortable" ).sortable();
				$( ".sortable" ).disableSelection();
				$(".frameRemover").unbind().click(function() {
					if (mp.mode !== 'edit') {
						return false;
					}
					var frame = $(this).attr("ref");
					$.ajax({
						url          : '/mapmanager/removeframe',
						type         : "POST",
						data         : {
							frame : frame
						},
						success      : function () {
							//$("#frameList li[framenum=" + frame + "]").remove();
							window.location.reload();
							console.log("That frame was deleted");
						},
						error        : function (data, stat, err) {
							console.log([ data, stat, err ]);
						}
					});
				});

				$(".frameHeader").unbind().click(function() {
					var frameV = $(this).parent().attr("framenum");
					$("#frameName").val($(this).html());
					$("#frameName").unbind().keyup(function() {
						$("#fh" + frameV).html($(this).val());
						usermap[frameV].name = $(this).val();
					});
				});
				$("#mapPropertiesM").modal('show');
			},
			error    : function (data, stat, err) {
				console.log([ data, stat, err ]);
			}
		});
	});

	$("#mapReset").click(function () {
		resetSession();
		$("#newMapM").modal('hide');
	});

	$("#mapDelete").click(function () {
		mapDelete();
	});

	$("#linkClose").click(function () {
		$("#mapLinkContainer").addClass("hide");
	});

	$("#submitSelection").click(function () {
		var target = $("#location_id").val(),
			a,
			image128 = getImageBySize(imageList, 'preview')[0],
			images32 = getImageBySize(imageList, 'small').join(" ");
		eObjects.each(function(item) {
			if (item.properties.get("ttl") === target) {
				item.properties.set({ imageList : imageList });
				item.properties.set({ img128    : image128 });
				item.properties.set({ images    : images32 });
				usermap[frame].objects[target].img = imageList;
				return true;
			}
		});
		eventListenersAdd();
		//console.log(usermap[target].img);
		/* здесь должно быть заполнение поля объекта данными */
		$("#imageM").modal("hide");
	});

	setupEnvironment();
}
ymaps.ready(init);

