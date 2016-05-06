<tr>
	<td><input type="text" class="userMapName" ref="<?=$hash_a;?>" name="<?=$hash_a;?>[]" value="<?=$name;?>"></td>
	<td>
		<img src="<?=$this->config->item("api");?>/images/map.png" width="16" height="16" border="0" alt="">
		<a  href="<?=$this->config->item("base_url");?>map/<?=$hash_a;?>" title="Нередактируемая карта"><?=$hash_a;?></a>
		<? if ($this->session->userdata("uidx") == $author) { ?>
		<br>
		<img src="<?=$this->config->item("api");?>/images/map_edit.png" width="16" height="16" border="0" alt="">
		<a  href="<?=$this->config->item("base_url");?>map/<?=$hash_e;?>" style="color:red" title="Редактируемая карта"><?=$hash_e;?></a>
		<? } ?>
	</td>
	<td>
		<center><input type="checkbox" class="userMapPublic" ref="<?=$hash_a;?>" name="<?=$hash_a;?>[]"<?=$public;?><?=$disable;?>></center>
	</td>
	<td>
		<button class="userMapNameSaver btn" ref="<?=$hash_a;?>"<?=$disable;?>><i class="icon-hdd"></i></button>
	</td>
</tr>