<div id="SContainer" class="well">
	<div id="YMHead" class="well">
		<div class="pull-left" style="margin-left:10px;">
			<i class="icon-move icon-white" style="margin:0px;padding:0px;vertical-align:middle;"></i> 
			<span style="margin:0px;padding:0px;vertical-align:middle">Редактор</span>
		</div>
		<i class="icon-chevron-down icon-white pull-right" id="navdown" style="margin-right:2px;"></i>
		<i class="icon-chevron-up  icon-white pull-right"  id="navup"   style="display:none;margin-right:2px;"></i>
	</div>
	<ul class="nav nav-tabs" id="navheader" style="height:40px;">
		<li id="palette" title="Средства создания объектов" class="active"><a href="#mainselector" data-toggle="tab">Я рисую</a></li>
		<li id="manager" title="Менеджер объектов"><a href="#results" data-toggle="tab">Уже нарисовано <span id="ResultHead2"></span></a></li>
	</ul>
	<div class="tab-content" id="navigator">
		<div id="mainselector" class="tab-pane active row-fluid">
			<div class="btn-group btn-small btn-block" data-toggle="buttons-radio" style="left:5px;margin-bottom:5px;width:98%">
				<button class="btn btn-info obj_sw" pr=0 title="Карта"><img src="<?=$this->config->item('api');?>/images/map.png" alt="map"></button>
				<button class="btn btn-info obj_sw active" pr=1 title="Простой маркер"><img src="<?=$this->config->item('api');?>/images/marker.png" alt="point"></button>
				<button class="btn btn-info obj_sw" pr=2 title="Ломаная"><img src="<?=$this->config->item('api');?>/images/layer-shape-polyline.png" alt="line"></button>
				<button class="btn btn-info obj_sw" pr=3 title="Участок"><img src="<?=$this->config->item('api');?>/images/layer-shape-polygon.png" alt="polygon"></button>
				<button class="btn btn-info obj_sw" pr=4 title="Круг"><img src="<?=$this->config->item('api');?>/images/layer-shape-ellipse.png" alt="circle"></button>
			</div>
			<div id="navigator-pane0" class="row-fluid navigator-pane hide">
				<div class="input-prepend" style="margin:2px 5px;">
					<span class="add-on">широта</span>
					<input type="text" id="vp_lat" placeholder="широта центра карты" title="Широта центра карты" class="mapcoord" style="margin:0px;width:130px;">
				</div>
				<div class="input-prepend" style="margin:2px 5px;">
					<span class="add-on">долгота</span><input type="text" id="vp_lon" placeholder="долгота центра карты" title="Долгота центра карты" class="mapcoord" style="margin:0px;width:130px;">
				</div>
				<button type="button" id="mapFix" class="btn btn-primary btn-small btn-block" style="width:230px;margin:5px 5px;" title="Не перемещать центр">Фиксировать центр</button>
				<a href="" target="_blank" class="btn btn-small btn-info" style="width:120px;margin-left:5px;margin-top:25px;"><i class="icon-question-sign icon-white"></i>&nbsp;Помощь</a>
			</div>

			<div id="navigator-pane1" class="row-fluid navigator-pane">
				<div class="input-prepend" style="margin:2px 5px;">
					<span class="add-on">широта</span>
					<input type="text" id="m_lat" placeholder="широта точки" title="широта точки" class="pointcoord" style="margin:0px;width:130px;">
				</div>
				<div class="input-prepend" style="margin:2px 5px;">
					<span class="add-on">долгота</span>
					<input type="text" id="m_lon" placeholder="долгота точки" title="долгота точки" class="pointcoord" style="margin:0px;width:130px;">
				</div>
				<button type="button" class="btn btn-primary" id="coordSetter" style="margin:5px 5px;width:230px;" title="Перемещает маркер в точку с указанными координатами">Установить координаты</button>
				<select name="m_style" id="m_style" style="margin:0px 5px;width:230px;" title="Стиль оформления для этого объекта">
					<option value="twirl#redDotIcon">Cтиль по умолчанию</option>
				</select>
			</div>

			<div id="navigator-pane2" class="row-fluid navigator-pane hide">
				<select name="line_style" id="line_style" style="margin:2px 5px;width:230px;" title="Стиль оформления для этого объекта">
					<option value="routes#default">Cтиль по умолчанию</option>
				</select>
				<div class="label label-info">Длина: <span id="line_len">0</span><span> м.</span></div>
				<div class="label label-info">Количество вершин: <span id="line_vtx">0</span></div>
			</div>

			<div id="navigator-pane3" class="row-fluid navigator-pane hide">
				<select name="polygon_style" id="polygon_style" style="margin:2px 5px;width:230px;" title="Стиль оформления для этого объекта">
				</select>
				<div class="label label-info">Периметр: <span id="polygon_len">0</span><span> м.</span></div>
				<div class="label label-info">Количество вершин: <span id="polygon_vtx">0</span></div>
			</div>

			<div id="navigator-pane4" class="row-fluid navigator-pane hide">
				<div class="input-prepend" style="margin:2px 5px;">
					<span class="add-on">широта</span><input type="text" id="cir_lat" placeholder="широта центра" title="широта центра" class="circlecoord">
				</div>
				<div class="input-prepend" style="margin:2px 5px;">
					<span class="add-on">долгота</span><input type="text" id="cir_lon" placeholder="долгота центра" title="долгота центра" class="circlecoord">
				</div>
				<div class="input-prepend input-append" style="margin:2px 5px;">
					<span class="add-on">радиус</span><input type="text" id="cir_radius" placeholder="радиус круга" title="радиус" class="circlecoord" value="100"><span class="add-on" style="margin:0px;width:20px;" title="Значение радиуса круга">м.</span>
				</div>
				<select name="circle_style" id="circle_style" style="margin:0px 5px;width:230px;"  title="Стиль оформления для этого объекта"></select>
				<div class="label label-info">Площадь: <span id="cir_field"></span>0<span> м.</span></div>
				<div class="label label-info">Окружность: <span id="cir_len"></span>0<span> м.<sup>2</sup></span></div>
			</div>
		</div>
		<div id="results" class="tab-pane">
			<input type="text" id="pointfilter" style="margin-left:4px;width:170px;height:24px;">
			<i class="icon-filter" style="vertical-align:middle;"></i>
			<div class="label label-info">Редактируется</div>
			<div id="nowEdited"></div>
			<div class="label label-info">Нарисовано</div>
			<div id="ResultBody"></div>
		</div>
	</div>
</div>