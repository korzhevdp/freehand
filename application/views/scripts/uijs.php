	var minOpacity = .15,
		maxOpacity = 1;

	$(".modal").modal("hide");

	$(function() {
		$("#SContainer").draggable({
			containment : "#YMapsID",
			scroll      : false,
			handle      : "#YMHead",
			stop        : function() {
				mp.nav = [$("#SContainer").css('top'), $("#SContainer").css('left')];
			}});
	});

	$(function() {
		$(".modal").draggable({
			containment : "body",
			scroll      : false,
			handle      : ".modal-header"
		});
	});

	/*
	$(function() {
		$('.mapName, #SContainer').delay(20000).animate({ opacity: minOpacity }, 2000, 'swing', function(){});
	});

	$('#SContainer').mouseenter(function() {
		$(this).dequeue().stop().animate({opacity: maxOpacity}, 200);
	});

	$('#SContainer').mouseleave(function() {
		$(this).delay(30000).animate({opacity: minOpacity}, 2000, 'swing', function(){});
	});

	$('.map_name').mouseleave(function() {
		$(this).delay(20000).animate({opacity: minOpacity}, 2000, 'swing', function(){});
	});

	$('.map_name').mouseenter(function() {
		$(this).dequeue().stop().animate({opacity: maxOpacity}, 100);
	});
	*/

	$('#YMHead').dblclick(function() {
		if($('#navigator').hasClass("hide")){
			$('#navigator, #navheader').removeClass("hide");
			$('#SContainer').css('height', 340);
			return false;
		}
		$('#navigator, #navheader').addClass("hide");
		$('#SContainer').css('height', 27);
	});

	$('#navup').unbind().click(function() {
		$(this).addClass("hide");
		$('#navdown').removeClass("hide");
		$('#navigator, #navheader').addClass("hide");
		$('#SContainer').css('height', 27);
	});

	$('#navdown').unbind().click(function() {
		$(this).addClass("hide");
		$('#navup').removeClass("hide");
		$('#navigator, #navheader').removeClass("hide");
		$('#SContainer').css('height', 340);
	});

	$("#pointfilter").keyup(function() {
		if ($("#pointfilter").val().length) {
			$(".mg-btn-list").each(function() {
				var test = $(this).html().toString().toLowerCase().indexOf($("#pointfilter").val().toString().toLowerCase()) + 1;
				(test) ? $(this).parent().removeClass("hide") : $(this).parent().addClass("hide");
			});
		}
	});

	$(".myMaps").click(function(e) {
		e.preventDefault();
		$.ajax({
			url      : "/mapmanager/getmaps",
			type     : "GET",
			dataType : "html",
			success  : function(data) {
				$("#myMapList").empty().append(data);
				$("#myMapsM").modal("show");
				renamerListen();
			},
			error    : function(data,stat,err) {
				console.log([data,stat,err].join("\n"));
			}
		});
	});

	function renamerListen() {
		$(".userMapNameSaver").unbind().click(function() {
			ref = $(this).attr("ref");
			$.ajax({
				url       : "/mapmanager/savemapname",
				type      : "POST",
				data      : {
					uhash : ref,
					name  : $(".userMapName[ref=" + ref + "]").val(),
					pub   : ($(".userMapPublic[ref=" + ref + "]").prop("checked")) ? 1 : 0
				},
				dataType  : "html",
				success   : function(data){
					$(this).addClass("btn-success");
					//console.log(data);
				},
				error     : function(data,stat,err){
					console.log([data,stat,err].join("\n"));
				}
			});
		});
	}

	$("#saveMaps").click(function() {
		$("#mapForm").submit();
	});

	$('#modal_pics').modal({ show: 0 });

	$('#modal_pics').on('show', function () {
		carousel_init();
	});

	$("#YMapsID").height($(window).height() - 54 + 'px');
	//$("#YMapsID").width($(window).width() - 4 + 'px');

