<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<title>�������� ��������: <?=$title;?> 1.0b</title>
<meta http-equiv="Content-Type" content="text/html; charset=windows-1251" />
<meta name="keywords" content="<?=$keywords;?>" />
<!-- API 2.0 -->
<script src="http://api-maps.yandex.ru/2.0/?coordorder=longlat&amp;load=package.full&amp;mode=debug&amp;lang=ru-RU" type="text/javascript"></script>
<script type="text/javascript" src="/jscript/map_styles2.js"></script>
<script type="text/javascript" src="/jscript/freehand_d.js"></script>
<!-- EOT API 2.0 -->
<link href="/bootstrap/css/bootstrap.css" rel="stylesheet" />
<link href="/jqueryui/css/jqueryui.css" rel="stylesheet" />
<link href="/css/freehand.css" rel="stylesheet" media="screen" type="text/css" />
</head>
<body>

<!-- ��������� -->
<div class="navbar">
	<div class="navbar-inner">
		<div class="container">
			<a class="brand span2" href="http://minigis.net"><img src="/images/minigis24.png" width="24" height="24" border="0" alt="" /> Minigis.NET</a>
			<?=$menu;?>
		</div>
	</div>
</div>

<div class="well span5 mapName map_name">
	<input type="text" name="mapName" id="mapName" class="pull-left" placeholder="ID �����..." title="������� ���� ID �����">
	<!-- <button type="button" class="btn btn-mini btn-primary">��������� ���</button> -->
	<div class="btn-toolbar" style="margin: 0;">
		<div class="btn-group" style="margin-left: 5px;">
			<a class="btn dropdown-toggle btn-small btn-info" data-toggle="dropdown" href="#">�����&nbsp;<span class="caret"></span></a>
			<ul class="dropdown-menu">
				<li><a href="#" id="mapLoader" title="���������� ����� � ��������� ���������������">���������</a></li>
				<li><a href="#" id="mapSave" title="���������� �������� ���������">���������</a></li>
				<li><a href="#" id="mapReset" title="������� ������ �������� (���������)">����� �����</a></li>
			</ul>
		</div>

		<div class="btn-group pull-right">
			<a class="btn dropdown-toggle btn-small btn-success" data-toggle="dropdown" href="#" title="���������� ������">���������� ������&nbsp;<span class="caret"></span></a>
			<ul id="linkFactory" class="dropdown-menu">
				<li id="ehashID"><a href="#" pr=1 title="���������� ������ �� ������������� �����" disabled><i class="readyMarker icon-remove"></i>&nbsp;������ �� ������������� �����</a></li>
				<li><a href="#" pr=2 title="���������� ������ �� ��������������� �����"  disabled><i class="readyMarker icon-remove"></i>&nbsp;������ �� ��������������� �����</a></li>
				<li><a href="#" pr=3 title="��������� ���� � ������������ ������������� ������" disabled><i class="readyMarker icon-remove"></i>&nbsp;������ ��� ����������� �� ����</a></li>
			</ul>
		</div>
	</div>
</div>
<!-- ��������� -->

<table class="main_page_body" id="main_table">
	<tr>
		<td id="YMapsID"><!-- ��� ����� -->
			<div id="SContainer" class="well">
				<div id="YMHead" class="well">
					<span class="pull-left" style="margin-left:10px;">
						<i class="icon-move icon-white" style="margin:0px;padding:0px;vertical-align:middle"></i> 
						��������
					</span>
					<i class="icon-chevron-down icon-white pull-right" id="navdown" style="display:none;"></i>
					<i class="icon-chevron-up icon-white pull-right" id="navup"></i>
				</div>
				<ul class="nav nav-tabs" id="navheader">
					<li id="palette" title="�������� �������� ��������" class="active"><a href="#mainselector" data-toggle="tab">� �����</a></li>
					<li id="manager" title="�������� ��������"><a href="#results" data-toggle="tab">��� ���������� <span id="ResultHead2"></span></a></li>
				</ul>


					<div class="tab-content" id="navigator">
						<div id="mainselector" class="tab-pane active row-fluid">
							<div class="btn-group span11" data-toggle="buttons-radio" style="left:5px;">
								<button class="btn btn-info obj_sw" pr=0 title="�����"><img src="/images/map.png" style="width:16px;height:16px;" alt="map"></button>
								<button class="btn btn-info obj_sw active" pr=1 title="������� ������"><img src="/images/marker.png" style="width:16px;height:16px;" alt="point" /></button>
								<button class="btn btn-info obj_sw" pr=2 title="�������"><img src="/images/layer-shape-polyline.png" style="width:16px;height:16px;" alt="line" /></button>
								<button class="btn btn-info obj_sw" pr=3 title="�������"><img src="/images/layer-shape-polygon.png" style="width:16px;height:16px;" alt="polygon" /></button>
								<button class="btn btn-info obj_sw" pr=4 title="����"><img src="/images/layer-shape-ellipse.png" style="width:16px;height:16px;" alt="circle" /></button>
							</div>

							<div id="navigator-pane0" class="row-fluid navigator-pane hide">
								<div class="input-prepend" style="margin:0px 5px;">
									<span class="add-on span4" style="margin:0px;">������</span><input type="text" id="vp_lat" placeholder="������ ������ �����" title="������ ������ �����" class="span7 mapcoord" style="margin:0px;" />
								</div>
								<div class="input-prepend" style="margin:0px 5px;">
									<span class="add-on span4" style="margin:0px;">�������</span><input type="text" id="vp_lon" placeholder="������� ������ �����" title="������� ������ �����" class="span7 mapcoord" style="margin:0px;" />
								</div>
								<button type="button" id="mapFix" class="btn btn-primary btn-mini" style="width:120px;margin-left:5px;margin-top:5px;" title="�� ���������� �����">����������� �����</button>
								<a href="http://minigis.net/index.php?id=13" target="_blank" class="btn btn-mini btn-info" style="width:105px;margin-left:5px;margin-top:25px;"><i class="icon-question-sign icon-white"></i>&nbsp;������</a>
							</div>

							<div id="navigator-pane1" class="row-fluid navigator-pane">
								<div class="input-prepend" style="margin:0px 5px;">
									<span class="add-on span4" style="margin:0px;">������</span><input type="text" id="m_lat" placeholder="������ �����" title="������ �����" class="span7 pointcoord" style="margin:0px" />
								</div>
								<div class="input-prepend" style="margin:0px 5px;">
									<span class="add-on span4" style="margin:0px;">�������</span><input type="text" id="m_lon" placeholder="������� �����" title="������� �����" class="span7 pointcoord" style="margin:0px" />
								</div>
								<button type="button" class="btn btn-primary btn-mini" id="coordSetter" style="margin:5px 5px;" title="���������� ������ � ����� � ���������� ������������">���������� ����������</button>
								<select name="m_style" id="m_style" class="span10" style="margin:0px 5px;">
									<option value="twirl#redDotIcon">����� �� ���������</option>
								</select>
							</div>

							<div id="navigator-pane2" class="row-fluid navigator-pane hide">
								<select name="line_style" id="line_style" class="span10" style="margin:0px 5px;">
									<option value="routes#default">����� �� ���������</option>
								</select>
								<div class="label label-info" style="margin:5px 5px;">�����: <span id="line_len">0</span><span> �.</span></div>
								<div class="label label-info" style="margin:5px 5px;">���������� ������: <span id="line_vtx">0</span></div>
							</div>

							<div id="navigator-pane3" class="row-fluid navigator-pane hide">
								<select name="polygon_style" id="polygon_style" class="span10" style="margin:0px 5px;">
								</select>
								<div class="label label-info" style="margin:5px 5px;">��������: <span id="polygon_len">0</span><span> �.</span></div>
								<div class="label label-info" style="margin:5px 5px;">���������� ������: <span id="polygon_vtx">0</span></div>
							</div>

							<div id="navigator-pane4" class="row-fluid navigator-pane hide">
								<div class="input-prepend" style="margin:0px 5px;">
									<span class="add-on span4" style="margin:0px;">������</span><input type="text" id="cir_lat" placeholder="������ ������" title="������ ������" class="span7 circlecoord" style="margin:0px;" />
								</div>
								<div class="input-prepend" style="margin:0px 5px;">
									<span class="add-on span4" style="margin:0px;">�������</span><input type="text" id="cir_lon" placeholder="������� ������" title="������� ������" class="span7 circlecoord" style="margin:0px;" />
								</div>
								<div class="input-prepend input-append" style="margin:0px 5px;">
									<span class="add-on span4" style="margin:0px;">������</span><input type="text" id="cir_radius" placeholder="������ �����" title="������" class="span5 circlecoord" style="margin:0px;" value="100" /><span class="add-on" style="margin:0px;" title="��������� ������� �����">�.</span>
								</div>
								<select name="circle_style" id="circle_style" class="span10" style="margin:0px 5px;">
								</select>
								<div class="label label-info" style="margin:5px 5px;">�������: <span id="cir_field"></span>0<span> �.</span></div>
								<div class="label label-info" style="margin:5px 5px;">����������: <span id="cir_len"></span>0<span> �.<sup>2</sup></span></div>
							</div>
						</div>
						<div id="results" class="tab-pane">
							<input type="text" id="pointfilter" style="margin-left:4px;width:170px;height:24px;">
							<i class="icon-filter" style="vertical-align:middle;"></i>
							<div class="label label-info" style="margin:5px 5px;">�������������</div>
							<div id="nowEdited"></div>
							<div class="label label-info" style="margin:5px 5px;">����������</div>
							<div id="ResultBody"></div>
						</div>
					</div>
				</div>
			</div>
		</td>
	</tr>
</table>

<?=$this->load->view('frontend/frontend_modal_pic',array(),true);?>
<!-- <div class="well span6" style="height:400px;position:absolute; top:200px; right:5px;overflow:auto">�������<pre><div id="consoleContent"></div></pre></div> -->

<div class="well hide container-fluid" id="mapLinkContainer" style="height:28px;padding:5px;position:absolute;top:45%; left:35%; width:30%">
	<input type="text" name="mapLink" id="mapLink" value="" class="span4 pull-left" />
	<button type="button" class="btn btn-small btn-primary pull-right" id="linkClose">�������</button>
</div>

<div class="hide"><?=$links_heap;?></div>
<form method="post" action="" style="display:none;">
	<input type="hidden" name="maphash" id="maphash" value="<?=$maphash;?>" />
	<input type="hidden" name="current_zoom" id="current_zoom" value="<?=$zoom;?>" />
	<input type="hidden" name="current_type" id="current_type" value="<?=$maptype;?>" />
	<input type="hidden" name="map_center" id="map_center" value="<?=$maps_center;?>" />
	<input type="hidden" name="current_obj_type" id="current_obj_type" value="1" />
	<input type="hidden" name="location_id" id="location_id" value="" />
</form>

<script type="text/javascript" src="/jscript/jquery.js"></script>
<script type="text/javascript" src="/jqueryui/js/jqueryui.js"></script>

<script type="text/javascript">
	<!--
	var height = $(window).height();
	$("#main_table").css("height",(height - 50) + "px");
	
	$("#YMapsID").focus();

	$(function() {
		$("#SContainer").draggable({containment: "#YMapsID", scroll: false, handle: "#YMHead" });
	});
	
	$(function() {
		$(".modal").draggable({containment: "body", scroll: false, handle: ".modal-header" });
	});
	
	$(function(){
		$('.mapName').delay(20000).animate({opacity: 0}, 2000, 'swing', function(){
			//$("#YMHead").animate({opacity: 1},200);
		});
	});

	$('#YMHead').dblclick(function() {
		if($('#navigator').css('display') == 'block'){
			$('#navigator, #navheader').css('display', 'none');
			$('#SContainer').css('height', 22);
		}else{
			$('#navigator, #navheader').css('display', 'block');
			$('#SContainer').css('height', 340);
		}
	});

	$('#navup').click(function() {
		$('#navigator, #navheader').css('display', 'none');
		$('#navup').css('display', 'none');
		$('#navdown').css('display', 'block');
		$('#SContainer').css('height', 22);
	});

	$('#navdown').click(function() {
		$('#navigator, #navheader').css('display', 'block');
		$('#navdown').css('display', 'none');
		$('#navup').css('display', 'block');
		$('#SContainer').css('height', 340);
	});
	
	$('#SContainer').mouseleave(function(){
		$(this).delay(30000).animate({opacity: 0.4}, 2000, 'swing', function(){
			//$("#YMHead").animate({opacity: 1},200);
		});
	});

	$('#SContainer').mouseenter(function(){
		$(this).dequeue().stop().animate({opacity: 1},200);
	});

	$('.mapName').mouseleave(function(){
		$(this).delay(20000).animate({opacity: 0.2}, 2000, 'swing', function(){
			//$("#YMHead").animate({opacity: 1},200);
		});
	});

	$('.mapName').mouseenter(function(){
		$(this).dequeue().stop().animate({opacity: 1},100);
	});

	$("#pointfilter").keyup(function(){
		//alert($("#pointfilter").val());
		if($("#pointfilter").val().length){
			$(".mg-btn-list").each(function(){
				var test = $(this).html().toString().toLowerCase().indexOf($("#pointfilter").val().toString().toLowerCase()) + 1;
				(test) ? $(this).parent().css('display','block') : $(this).parent().css('display','none');
			});
		}
	});
//-->
</script>

<script type="text/javascript" src="/bootstrap/js/bootstrap-transition.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-alert.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-modal.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-dropdown.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-scrollspy.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-tab.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-tooltip.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-popover.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-button.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-collapse.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-carousel.js"></script>
<script type="text/javascript" src="/bootstrap/js/bootstrap-typeahead.js"></script>
<?=$footer;?>
</body>
</html>