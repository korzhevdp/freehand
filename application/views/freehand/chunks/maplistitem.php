<tr>
	<td><input type="text" class="userMapName" ref="<?=$hash_a;?>" name="<?=$hash_a;?>[]" value="<?=$name;?>"></td>
	<td>
		<img src="<?=$this->config->item("api");?>/images/map.png" width="16" height="16" border="0" alt="">
		<a  href="<?=$this->config->item("base_url");?>freehand/map/<?=$hash_a;?>" title="��������������� �����"><?=$hash_a;?></a><br>
		<img src="<?=$this->config->item("api");?>/images/map_edit.png" width="16" height="16" border="0" alt="">
		<a  href="<?=$this->config->item("base_url");?>freehand/map/<?=$hash_e;?>" style="color:red" title="������������� �����"><?=$hash_e;?></a></td>
	<td>
		<center><input type="checkbox" class="userMapPublic" ref="<?=$hash_a;?>" name="<?=$hash_a;?>[]"<?=$public;?>></center>
	</td>
	<td>
		<button class="userMapNameSaver btn" ref="<?=$hash_a;?>"><i class="icon-tag"></i></button>
	</td>
</tr>