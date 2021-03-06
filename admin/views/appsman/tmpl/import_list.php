<?php
/**
 * @version 1.5 stable $Id: import.php 1883 2014-04-09 17:49:21Z ggppdk $
 * @package Joomla
 * @subpackage FLEXIcontent
 * @copyright (C) 2009 Emmanuel Danan - www.vistamedia.fr
 * @license GNU/GPL v2
 *
 * FLEXIcontent is a derivative work of the excellent QuickFAQ component
 * @copyright (C) 2008 Christoph Lukes
 * see www.schlu.net for more information
 *
 * FLEXIcontent is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 */

defined('_JEXEC') or die('Restricted access');

$params = $this->cparams;
$document	= JFactory::getDocument();

// For tabsets/tabs ids (focusing, etc)
$tabSetCnt = -1;
$tabSetMax = -1;
$tabCnt = array();
$tabSetStack = array();

// Load JS tabber lib
$this->document->addScriptVersion(JUri::root(true).'/components/com_flexicontent/assets/js/tabber-minimized.js', FLEXI_VHASH);
$this->document->addStyleSheetVersion(JUri::root(true).'/components/com_flexicontent/assets/css/tabber.css', FLEXI_VHASH);
$this->document->addScriptDeclaration(' document.write(\'<style type="text/css">.fctabber{display:none;}<\/style>\'); ');  // temporarily hide the tabbers until javascript runs

?>

<div class="flexicontent" id="flexicontent">

<form action="index.php" method="post" name="adminForm" id="adminForm" class="form-validate form-horizontal" enctype="multipart/form-data" >

<?php if (!empty( $this->sidebar)) : ?>
<div class="<?php echo FLEXI_J40GE ? 'row' : 'row-fluid'; ?>">
	<div id="j-sidebar-container" class="span2 col-md-2">
		<?php echo str_replace('type="button"', '', $this->sidebar); ?>
	</div>
	<div class="span10 col-md-10">
		<div id="j-main-container">
<?php else : ?>
<div class="<?php echo FLEXI_J40GE ? 'row' : 'row-fluid'; ?>">
	<div class="span12 col-md-12">
		<div id="j-main-container">
<?php endif;?>

	<?php
	array_push($tabSetStack, $tabSetCnt);
	$tabSetCnt = ++$tabSetMax;
	$tabCnt[$tabSetCnt] = 0;
	?>
	
	<?php
	$xml = simplexml_load_string($this->conf['xml']);
	
	if (!$xml)
		echo 'Could not parse XML file';
	
	else foreach ($xml->rows as $table)
	{
		$table_name = (string)$table->attributes()->table;
		$table_name = ucfirst(str_replace('flexicontent_', '', $table_name));
		$rows = $table->row;
		
		if (!count($rows)) {
			echo "no rows <br/>";
			continue;
		}
		
		$row = $rows[0];
		echo '<h1>'.$table_name.'</h1>';
		echo '<table class="table fcmanlist">'."\n";
		echo '<thead>'."\n";
		echo "<tr>\n";
		foreach($row as $prop => $value) {
			if ($prop=='attribs') continue;
			echo '<th>'.$prop.'</th>';
		}
		echo "</tr>\n";
		echo '</thead>'."\n";
		
		echo '<tbody>'."\n";
		foreach($rows as $row) {
			echo "<tr>\n";
			foreach($row as $prop => $value) {
				if ($prop=='attribs') continue;
				echo '<td style="text-align:center;">'.trim($value,'"').'</td>';
			}
			echo "</tr>\n";
		}
		echo '</tbody>'."\n";
		echo '</table>'."\n";
	}
	?>
		<input type="hidden" name="option" value="com_flexicontent" />
		<input type="hidden" name="controller" value="appsman" />
		<input type="hidden" name="view" value="appsman" />
		<input type="hidden" name="task" value="" />
		<input type="hidden" name="fcform" value="1" />
		<?php echo JHtml::_( 'form.token' ); ?>
		
		<!-- fc_perf -->
		</div>  <!-- j-main-container -->
	</div>  <!-- spanNN -->
</div>  <!-- row -->
</form>
</div><!-- #flexicontent end -->