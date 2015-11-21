<?php
$FT = 'MGALLERY';
$PRV_TYPE='0';
$image_placeholder = 'data:image/gif;base64,R0lGODlhAQABAAD/ACwAAAAAAQABAAACADs=';
$fields_box_placing = $field->parameters->get('fields_box_placing', '1');
$form_file_preview = $field->parameters->get('form_file_preview', '1');

$n = 0;
foreach($field->value as $file_id)
{
	$file_data = $files_data[$file_id];
	$fieldname_n = $fieldname.'['.$n.']';
	$elementid_n = $elementid.'_'.$n;
	$filename_original = $file_data->filename_original ? $file_data->filename_original : $file_data->filename;
	
	if ( !in_array(strtolower($file_data->ext), $imageexts)) {
		$src = $image_placeholder;
		$style = 'display:none;';
	} else {
		$img_path = (substr($file_data->filename, 0,7)!='http://' || substr($file_data->filename, 0,8)!='https://') ?
			JURI::root(true) . '/' . (empty($file_data->secure) ? $mediapath : $docspath) . '/' . $file_data->filename :
			$file_data->filename ;
		$src = JURI::root().'components/com_flexicontent/librairies/phpthumb/phpThumb.php?src=' . $img_path . '&amp;w=100&amp;h=100&amp;zc=1';
		$style = 'width:100px; height:100px;';
	}
	
	$info_txt_classes = $file_data->published ? '' : 'file_unpublished hasTooltip';
	$info_txt_tooltip = $file_data->published ? '' : 'title="'.flexicontent_html::getToolTip('FLEXI_FILE_FIELD_FILE_UNPUBLISHED', 'FLEXI_FILE_FIELD_FILE_UNPUBLISHED_DESC', 1, 1).'"';
	$_select_file_lbl = '
			<label class="label inlinefile-data-lbl '.$tip_class.'" title="'.flexicontent_html::getToolTip('FLEXI_FIELD_'.$FT.'_ABOUT_SELECTED_FILE', 'FLEXI_FIELD_'.$FT.'_ABOUT_SELECTED_FILE_DESC', 1, 1).'" id="'.$elementid_n.'_file-data-lbl" for="'.$elementid_n.'_file-data-txt">
				'.($fields_box_placing==1 ? $field->label.' - ' : ''). JText::_('FLEXI_FIELD_'.$FT.'_SELECTED_FILE').'
			</label>
	';
	
	$field->html[] = '
	<div class="fcclear"></div>
	<div style="display:block;">
		<div class="fc_filedata_txt_nowrap nowrap_hidden">'.$filename_original . ($file_data->url ? ' ['.$file_data->altname.']' : '').'</div>
		<input class="fc_filedata_txt inlinefile-data-txt '. $info_txt_classes . $required_class .'" readonly="readonly" name="'.$fieldname_n.'[file-data-txt]" id="'.$elementid_n.'_file-data-txt" '.$info_txt_tooltip.' value="'.$filename_original . ($file_data->url ? ' ['.$file_data->altname.']' : '').'" />
		<br/>
		'.($form_file_preview==1 ? '<img id="'.$elementid_n.'_img_preview" src="'.$src.'" class="fc_preview_thumb" style="'.$style.'" alt="Preview image placeholder"/>' : '').'
	</div>
	<table class="fc-form-tbl fcinner inlinefile-tbl">
	<tr class="inlinefile-data-row">
		'.($fields_box_placing==1 ? '' : '
		<td class="key inlinefile-data-lbl-cell">
			'.$_select_file_lbl.'
		</td>').'
		<td class="inlinefile-data-cell" '.($fields_box_placing==1 ? 'colspan="2"' : '').'>
			<span class="inlinefile-data">
				'.($fields_box_placing==1 ? '<span style="visibility:hidden; z-index:-1; position:absolute;">'.$_select_file_lbl.'</span>' : '').'
				<input type="hidden" id="'.$elementid_n.'_file-id" name="'.$fieldname_n.'[file-id]" value="'.$file_id.'" class="fc_fileid" />'.'
				<span class="fc_fileupload_box btn btn-info">
					<span>'.JText::_('FLEXI_FIELD_'.$FT.'_UPLOAD_NEW').'</span>
					<input type="file" id="'.$elementid_n.'_file-data" name="'.$fieldname_n.'[file-data]" class="fc_filedata" data-rowno="'.$n.'" onchange="var file_box = jQuery(this).parent().parent().parent(); fc_loadImagePreview(this.id,\''.$elementid.'_\'+jQuery(this).attr(\'data-rowno\')+\'_img_preview\', \''.$elementid.'_\'+jQuery(this).attr(\'data-rowno\')+\'_file-data-txt\', 100, 0, \''.$PRV_TYPE.'\'); file_box.find(\'.inlinefile-secure-data\').show(400);  file_box.find(\'.inlinefile-secure-info\').hide(400); file_box.find(\'.inlinefile-del\').removeAttr(\'checked\').trigger(\'change\'); " />
				</span>
				<a class="btn btn-info addfile_'.$field->id.'" id="'.$elementid_n.'_addfile" title="'.$_prompt_txt.'" href="'.sprintf($addExistingURL, '__rowno__', '__thisid__').'" data-rowno="'.$n.'">
					'.JText::_('FLEXI_FIELD_'.$FT.'_MY_FILES').'
				</a>
			</span>
			
			'.( (!$multiple || $is_ingroup) && !$required_class ? '
			<br/>
			<input type="checkbox" id="'.$elementid_n.'_file-del" class="inlinefile-del" name="'.$fieldname_n.'[file-del]" value="1" onchange="file_fcfield_del_existing_value'.$field->id.'(this);" />
			<label class="label inlinefile-del-lbl '.$tip_class.'" title="'.flexicontent_html::getToolTip('FLEXI_FIELD_'.$FT.'_ABOUT_REMOVE_FILE', 'FLEXI_FIELD_'.$FT.'_ABOUT_REMOVE_FILE_DESC', 1, 1).'" id="'.$elementid_n.'_file-del-lbl" for="'.$elementid_n.'_file-del" >
				'.JText::_( 'Remove file' ).'
			</label>
			' : ( (!$multiple || $is_ingroup) && $required_class ? '<br/><div class="alert alert-info fc-small fc-iblock">'.JText::_('FLEXI_FIELD_'.$FT.'_REQUIRED_UPLOAD_NEW_TO_REPLACE').'</div>' : '')).'
		</td>
	</tr>'.
	
	( $iform_title ? '
	<tr class="inlinefile-title-row">
		<td class="key inlinefile-title-lbl-cell">
			<label class="label inlinefile-title-lbl '.$tip_class.'" title="'.flexicontent_html::getToolTip('FLEXI_FILE_DISPLAY_TITLE', 'FLEXI_FILE_DISPLAY_TITLE_DESC', 1, 1).'" id="'.$elementid_n.'_file-title-lbl" for="'.$elementid_n.'_file-title">
				'.JText::_( 'FLEXI_FILE_DISPLAY_TITLE' ).'
			</label>
		</td>
		<td class="inlinefile-title-data-cell">
			<span class="inlinefile-title-data">
				<input type="text" id="'.$elementid_n.'_file-title" size="44" name="'.$fieldname_n.'[file-title]" value="'.htmlspecialchars(!isset($form_data[$file_id]) ? $file_data->altname : $form_data[$file_id]['file-title'], ENT_COMPAT, 'UTF-8').'" class="fc_filetitle '.$required_class.'" />
			</span>
		</td>
	</tr>' : '').
	
	( $iform_lang ? '
	<tr class="inlinefile-lang-row">
		<td class="key inlinefile-lang-lbl-cell">
			<label class="label inlinefile-lang-lbl '.$tip_class.'" title="'.flexicontent_html::getToolTip('FLEXI_LANGUAGE', 'FLEXI_FILE_LANGUAGE_DESC', 1, 1).'" id="'.$elementid_n.'_file-lang-lbl" for="'.$elementid_n.'_file-lang">
				'.JText::_( 'FLEXI_LANGUAGE' ).'
			</label>
		</td>
		<td class="inlinefile-lang-data-cell">
			<span class="inlinefile-lang-data">
				'.flexicontent_html::buildlanguageslist($fieldname_n.'[file-lang]', 'class="fc_filelang use_select2_lib"', (!isset($form_data[$file_id]) ? $file_data->language : $form_data[$file_id]['file-lang']), 1).'
			</span>
		</td>
	</tr>' : '').
	
	( $iform_desc ? '
	<tr class="inlinefile-desc-row">
		<td class="key inlinefile-desc-lbl-cell">
			<label class="label inlinefile-desc-lbl '.$tip_class.'" title="'.flexicontent_html::getToolTip('FLEXI_DESCRIPTION', 'FLEXI_FILE_DESCRIPTION_DESC', 1, 1).'" id="'.$elementid_n.'_file-desc-lbl" for="'.$elementid_n.'_file-desc">
				'.JText::_( 'FLEXI_DESCRIPTION' ).'
			</label>
		</td>
		<td class="inlinefile-desc-data-cell">
			<span class="inlinefile-desc-data">
				<textarea id="'.$elementid_n.'_file-desc" cols="24" rows="3" name="'.$fieldname_n.'[file-desc]" class="fc_filedesc">'.(!isset($form_data[$file_id]) ? $file_data->description : $form_data[$file_id]['file-desc']).'</textarea>
			</span>
		</td>
	</tr>' : '').
	
	( $iform_dir ? '
	<tr class="inlinefile-secure-row">
		<td class="key inlinefile-secure-lbl-cell">
			<label class="label inlinefile-secure-lbl '.$tip_class.'" data-placement="top" title="'.flexicontent_html::getToolTip('FLEXI_URL_SECURE', 'FLEXI_URL_SECURE_DESC', 1, 1).'" id="'.$elementid_n.'_secure-lbl">
				'.JText::_( 'FLEXI_URL_SECURE' ).'
			</label>
		</td>
		<td class="inlinefile-secure-data-cell">
			'.($has_values ? '
			<span class="inlinefile-secure-info" style="'.(!$has_values ? 'display:none;' : '').'">
				<span class="badge badge-info">'.JText::_($file_data->secure ?  'FLEXI_YES' : 'FLEXI_NO').'</span>
			</span>' : '').'
			<span class="inlinefile-secure-data" style="'.($has_values ? 'display:none;' : '').'">
				'.flexicontent_html::buildradiochecklist( array(1=> JText::_( 'FLEXI_YES' ), 0=> JText::_( 'FLEXI_NO' )) , $fieldname_n.'[secure]', (!isset($form_data[$file_id]) ? 1 : (int)$form_data[$file_id]['secure']), 0, ' class="fc_filedir" ', $elementid_n.'_secure').'
			</span>
		</td>
	</tr>' : '').
	'
	</table>
	<div class="fcclear"></div>'
	;
	$n++;
}