<?php
defined( '_JEXEC' ) or die( 'Restricted access' );
use Joomla\String\StringHelper;

/*
 * CLASS with common methods for handling interaction with DB
 */
class flexicontent_db
{
	/**
	 * Method to set value for custom field 's common data types (INTEGER, DECIMAL(65,15), DATETIME)
	 * 
	 * @return string
	 * @since 1.5
	 */
	static function setValues_commonDataTypes($obj, $all=false)
	{
		$db = JFactory::getDBO();
		$query = 'UPDATE #__flexicontent_fields_item_relations'
			. ' SET value_integer = CAST(value AS SIGNED), value_decimal = CAST(value AS DECIMAL(65,15)), value_datetime = CAST(value AS DATETIME) '
			. (!$all ? ' WHERE item_id = ' . (int) $obj->item_id . ' AND field_id = ' . (int) $obj->field_id . ' AND valueorder = ' . (int) $obj->valueorder. ' AND suborder = ' . (int) $obj->suborder : '');
		$db->setQuery($query);
		$db->execute();
	}

	
	/**
	 * Method to verify a record has valid JSON data for the given column
	 * 
	 * @return string
	 * @since 3.1
	 */
	static function check_fix_JSON_column($colname, $tblname, $idname, $id, & $attribs=null)
	{
		$db = JFactory::getDBO();
		
		// This extra may seem redudant, but it is to avoid clearing valid data, due to coding or other errors
		$db->setQuery('SELECT '.$colname.' FROM #__'.$tblname.' WHERE '.$idname.' = ' . $db->Quote($id));
		$attribs = $db->loadResult();

		try {
			$json_data = new JRegistry($attribs);
		}
		catch (Exception $e)
		{
			$attribs = '{}';
			$json_data = new JRegistry($attribs);
			$db->setQuery('UPDATE #__'.$tblname.' SET '.$colname.' = '.$db->Quote($attribs).' WHERE '.$idname.' = ' .  $db->Quote($id));
			$db->execute();
			$app = JFactory::getApplication();
			if ($app->isAdmin())
			{
				$app->enqueueMessage('Cleared bad JSON COLUMN: <b>'.$colname.'</b>, DB TABLE: <b>'.$tblname.'</b>, RECORD: <b>'.$id.'</b>', 'warning');
			}
		}

		return $json_data;
	}

	
	/**
	 * Method to get the (language filtered) name of all access levels
	 * 
	 * @return string
	 * @since 1.5
	 */
	static function getAccessNames($accessid=null)
	{
		static $access_names = array();
		
		if ( $accessid!==null && isset($access_names[$accessid]) ) return $access_names[$accessid];
		
		$db = JFactory::getDBO();
		$db->setQuery('SELECT id, title FROM #__viewlevels');
		$_arr = $db->loadObjectList();
		$access_names = array(0=>'Public');  // zero does not exist in J2.5+ but we set it for compatibility
		foreach ($_arr as $o) $access_names[$o->id] = JText::_($o->title);
		
		if ( $accessid )
			return isset($access_names[$accessid]) ? $access_names[$accessid] : 'not found access id: '.$accessid;
		else
			return $access_names;
	}
	
	
	/**
	 * Method to get the type parameters of an item
	 * 
	 * @return string
	 * @since 1.5
	 */
	static function getTypeAttribs($force = false, $typeid)
	{
		static $typeparams = array();
		
		if ( !$force && isset($typeparams[$typeid]) ) return $typeparams[$typeid];
		
		$db = JFactory::getDBO();
		$query	= 'SELECT t.id, t.attribs'
			. ' FROM #__flexicontent_types AS t'
			.( $typeid ? ' WHERE t.id = ' . (int)$typeid : '')
			;
		$db->setQuery($query);
		if ( $typeid ) {
			$data = $db->loadObject();
			if (!$data) return false;
			
			$typeid = $data->id;
			$typeparams[$typeid] = $data->attribs;
			return $typeparams[$typeid];
		}
		else {
			$rows = $db->loadObjectList();
			foreach($rows as $data) {
				$typeid = $data->id;
				$typeparams[$typeid] = $data->attribs;
			}
			return $typeparams;
		}
	}
	
	/**
	 * Method to get the nr of favourites of anitem
	 *
	 * @access	public
	 * @return	integer on success
	 * @since	1.0
	 */
	static function getFavourites($type, $item_id)
	{
		$db = JFactory::getDBO();
		
		$query = '
			SELECT COUNT(id) AS favs
			FROM #__flexicontent_favourites
			WHERE itemid = '.(int)$item_id.'
				AND type = '.(int)$type;
		$db->setQuery($query);
		
		return $db->loadResult();
	}
	
	
	/**
	 * Method to get the nr of favourites of an user
	 *
	 * @access	public
	 * @since	1.0
	 */
	static function getFavoured($type, $item_id, $user_id)
	{
		$db = JFactory::getDBO();
		
		$query = '
			SELECT COUNT(id) AS fav
			FROM #__flexicontent_favourites
			WHERE itemid = '.(int)$item_id.'
				AND userid = '.(int)$user_id.'
				AND type = '.(int)$type;
		$db->setQuery($query);
		
		return $db->loadResult();
	}
	
	
	/**
	 * Method to remove a favourite
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.0
	 */
	static function removefav($type, $item_id, $user_id)
	{
		$db = JFactory::getDBO();
		
		$query = '
			DELETE FROM #__flexicontent_favourites
			WHERE itemid = '.(int)$item_id.'
				AND userid = '.(int)$user_id.'
				AND type = '.(int)$type;
		$db->setQuery($query);
		
		return $db->execute();
	}
	
	
	/**
	 * Method to add a favourite
	 *
	 * @access	public
	 * @return	boolean	True on success
	 * @since	1.0
	 */
	static function addfav($type, $item_id, $user_id)
	{
		$db = JFactory::getDBO();
		
		$obj = new stdClass();
		$obj->itemid = (int)$item_id;
		$obj->userid = (int)$user_id;
		$obj->type   = (int)$type;
		
		return $db->insertObject('#__flexicontent_favourites', $obj);
	}
	
	
	/*
	 * Retrieve author/user configuration
	 *
	 * @return object
	 * @since 1.5
	 */
	static function getUserConfig($user_id)
	{
		$db = JFactory::getDBO();
		
		$db->setQuery('SELECT author_basicparams FROM #__flexicontent_authors_ext WHERE user_id = ' . $user_id);
		$authorparams = $db->loadResult();
		$authorparams = new JRegistry($authorparams);
		
		return $authorparams;
	}
	
	
	/*
	 * Find stopwords and too small words
	 *
	 * @return array
	 * @since 1.5
	 */
	static function removeInvalidWords($words, &$stopwords, &$shortwords, $tbl='flexicontent_items_ext', $col='search_index', $isprefix=1)
	{
		$db     = JFactory::getDBO();
		$app    = JFactory::getApplication();
		$option = JRequest::getVar('option');
		$min_word_len = $app->getUserState( $option.'.min_word_len', 0 );
		
		$_word_clause = $isprefix ? '+%s*' : '+%s';
		$query = 'SELECT '.$col
			.' FROM #__'.$tbl
			.' WHERE MATCH ('.$col.') AGAINST ("'.$_word_clause.'" IN BOOLEAN MODE)'
			.' LIMIT 1';
		$_words = array();
		foreach ($words as $word) {
			$quoted_word = $db->escape($word, true);
			$q = sprintf($query, $quoted_word);
			$db->setQuery($q);
			$result = $db->loadAssocList();
			if ( !empty($result) ) {
				$_words[] = $word;      // word found
			} else if ( StringHelper::strlen($word) < $min_word_len ) {
				$shortwords[] = $word;  // word not found and word too short
			} else {
				$stopwords[] = $word;   // word not found
			}
		}
		return $_words;
	}
	
	/**
	 * Helper method to execute an SQL file containing multiple queries
	 *
	 * @return object
	 * @since 1.5
	 */
	static function execute_sql_file($sql_file)
	{
		$queries = file_get_contents( $sql_file );
		$queries = preg_split("/;+(?=([^'|^\\\']*['|\\\'][^'|^\\\']*['|\\\'])*[^'|^\\\']*[^'|^\\\']$)/", $queries);
		
		$db = JFactory::getDBO();
		foreach ($queries as $query) {
			$query = trim($query);
			if (!$query) continue;
			
			$db->setQuery($query);
			$result = $db->execute();
			if ($db->getErrorNum())  JFactory::getApplication()->enqueueMessage(__FUNCTION__.'(): SQL QUERY ERROR:<br/>'.nl2br($db->getErrorMsg()),'error');
		}
	}
	
	
	/**
	 * Helper method to execute a query directly, bypassing Joomla DB Layer
	 *
	 * @return object
	 * @since 1.5
	 */
	static function & directQuery($query, $assoc = false, $unbuffered = false)
	{
		$db     = JFactory::getDBO();
		$app = JFactory::getApplication();
		$dbprefix = $app->getCfg('dbprefix');
		$dbtype   = $app->getCfg('dbtype');
		$dbtype   = $dbtype == 'mysql' && !function_exists('mysql_query') ? 'mysqli' : $dbtype;  // PHP 7 removes mysql but 'mysql' database may still be in the configuration file

		$query = $db->replacePrefix($query);  //echo "<pre>"; print_r($query); echo "\n\n";
		$db_connection = $db->getConnection();
		
		$data = array();
		if ($dbtype == 'mysqli' )
		{
			$result = $unbuffered ?
				mysqli_query( $db_connection , $query, MYSQLI_USE_RESULT ) :
				mysqli_query( $db_connection , $query ) ;
			if ($result===false)
				throw new Exception('error '.__FUNCTION__.'():: '.mysqli_error($db_connection));
			
			if ($assoc) {
				while($row = mysqli_fetch_assoc($result)) $data[] = $row;
			} else {
				while($row = mysqli_fetch_object($result)) $data[] = $row;
			}
			mysqli_free_result($result);
		}
		
		else if ( $dbtype == 'mysql' )
		{
			$result = $unbuffered ?
				mysql_unbuffered_query( $query, $db_connection ) :
				mysql_query( $query, $db_connection  ) ;
			
			if ($result===false)
				throw new Exception('error '.__FUNCTION__.'():: '.mysql_error($db_connection));
				
			if ($assoc) {
				while($row = mysql_fetch_assoc($result)) $data[] = $row;
			} else {
				while($row = mysql_fetch_object($result)) $data[] = $row;
			}
			mysql_free_result($result);
		}
		
		else
		{
			throw new Exception( __FUNCTION__.'(): direct db query, unsupported DB TYPE' );
		}

		return $data;
	}


	/**
	 * Build the order clause of item listings
	 * precedence: $request_var ==> $order ==> $config_param ==> $default_order_col (& $default_order_dir)
	 * @access private
	 * @return string
	 */
	static function buildItemOrderBy(&$params=null, &$order='', $request_var='orderby', $config_param='orderby', $i_as='i', $rel_as='rel', $default_order_col_1st='', $default_order_dir_1st='', $sfx='', $support_2nd_lvl=false)
	{
		// Use global params ordering if parameters were not given
		if (!$params) $params = JComponentHelper::getParams( 'com_flexicontent' );
		
		$order_fallback = 'rdate';  // Use as default or when an invalid ordering is requested
		$orderbycustomfield   = (int) $params->get('orderbycustomfield'.$sfx, 1);    // Backwards compatibility, defaults to enabled *
		$orderbycustomfieldid = (int) $params->get('orderbycustomfieldid'.$sfx, 0);  // * but this needs to be set in order for field ordering to be used
		
		// 1. If a FORCED -ORDER- is not given, then use ordering parameters from configuration. NOTE: custom field ordering takes priority
		if (!$order) {
			$order = ($orderbycustomfield && $orderbycustomfieldid)  ?  'field'  :  $params->get($config_param.$sfx, $order_fallback);
		}
		
		// 2. If allowing user ordering override, then get ordering from HTTP request variable
		$order = $params->get('orderby_override') && ($request_order = JRequest::getVar($request_var.$sfx)) ? $request_order : $order;
		
		// 3. Check various cases of invalid order, print warning, and reset ordering to default
		if ($order=='field' && !$orderbycustomfieldid ) {
			// This can occur only if field ordering was requested explicitly, otherwise an not set 'orderbycustomfieldid' will prevent 'field' ordering
			echo "Custom field ordering was selected, but no custom field is selected to be used for ordering<br/>";
			$order = $order_fallback;
		}
		if ($order=='commented') {
			if (!file_exists(JPATH_SITE.DS.'components'.DS.'com_jcomments'.DS.'jcomments.php')) {
				echo "jcomments not installed, you need jcomments to use 'Most commented' ordering OR display comments information.<br/>\n";
				$order = $order_fallback;
			} 
		}
		
		$order_col_1st = $default_order_col_1st;
		$order_dir_1st = $default_order_dir_1st;
		flexicontent_db::_getOrderByClause($params, $order, $i_as, $rel_as, $order_col_1st, $order_dir_1st, $sfx);
		$order_arr[1] = $order;
		$orderby = ' ORDER BY '.$order_col_1st.' '.$order_dir_1st;
		
		
		// ****************************************************************
		// 2nd level ordering, (currently only supported when no SFX given)
		// ****************************************************************
		
		if ($sfx!='' || !$support_2nd_lvl) {
			$orderby .= $order_col_1st != $i_as.'.title'  ?  ', '.$i_as.'.title'  :  '';
			$order_arr[2] = '';
			$order = $order_arr;
			return $orderby;
		}
		
		$order = '';  // Clear this, thus force retrieval from parameters (below)
		$sfx='_2nd';  // Set suffix of second level ordering
		$order_fallback = 'alpha';  // Use as default or when an invalid ordering is requested
		$orderbycustomfield   = (int) $params->get('orderbycustomfield'.$sfx, 1);    // Backwards compatibility, defaults to enabled *
		$orderbycustomfieldid = (int) $params->get('orderbycustomfieldid'.$sfx, 0);  // * but this needs to be set in order for field ordering to be used
		
		// 1. If a FORCED -ORDER- is not given, then use ordering parameters from configuration. NOTE: custom field ordering takes priority
		if (!$order) {
			$order = ($orderbycustomfield && $orderbycustomfieldid)  ?  'field'  :  $params->get($config_param.$sfx, $order_fallback);
		}
		
		// 2. If allowing user ordering override, then get ordering from HTTP request variable
		$order = $request_var && ($request_order = JRequest::getVar($request_var.$sfx)) ? $request_order : $order;
		
		// 3. Check various cases of invalid order, print warning, and reset ordering to default
		if ($order=='field' && !$orderbycustomfieldid ) {
			// This can occur only if field ordering was requested explicitly, otherwise an not set 'orderbycustomfieldid' will prevent 'field' ordering
			echo "Custom field ordering was selected, but no custom field is selected to be used for ordering<br/>";
			$order = $order_fallback;
		}
		if ($order=='commented') {
			if (!file_exists(JPATH_SITE.DS.'components'.DS.'com_jcomments'.DS.'jcomments.php')) {
				echo "jcomments not installed, you need jcomments to use 'Most commented' ordering OR display comments information.<br/>\n";
				$order = $order_fallback;
			} 
		}
		
		$order_col_2nd = '';
		$order_dir_2nd = '';
		if ($order!='default') {
			flexicontent_db::_getOrderByClause($params, $order, $i_as, $rel_as, $order_col_2nd, $order_dir_2nd, $sfx);
			$order_arr[2] = $order;
			$orderby .= ', '.$order_col_2nd.' '.$order_dir_2nd;
		}
		
		// Order by title after default ordering
		$orderby .= ($order_col_1st != $i_as.'.title' && $order_col_2nd != $i_as.'.title')  ?  ', '.$i_as.'.title'  :  '';
		$order = $order_arr;
		return $orderby;
	}
	
	
	// Create order clause sub-parts
	static function _getOrderByClause(&$params, &$order='', $i_as='i', $rel_as='rel', &$order_col='', &$order_dir='', $sfx='')
	{
		// 'order' contains a symbolic order name to indicate using the category / global ordering setting
		switch ($order) {
			case 'date': case 'addedrev': /* 2nd is for module (PARAMETER FORM ELEMENT: fcordering) */
				$order_col	= $i_as.'.created';
				$order_dir	= 'ASC';
				break;
			case 'rdate': case 'added': /* 2nd is for module (PARAMETER FORM ELEMENT: fcordering) */
				$order_col	= $i_as.'.created';
				$order_dir	= 'DESC';
				break;
			case 'modified': case 'updated': /* 2nd is for module (PARAMETER FORM ELEMENT: fcordering) */
				$order_col	= $i_as.'.modified';
				$order_dir	= 'DESC';
				break;
			case 'published':
				$order_col	= $i_as.'.publish_up';
				$order_dir	= 'DESC';
				break;
			case 'published_oldest':
				$order_col	= $i_as.'.publish_up';
				$order_dir	= 'ASC';
				break;
			case 'expired':
				$order_col	= $i_as.'.publish_down';
				$order_dir	= 'DESC';
				break;
			case 'expired_oldest':
				$order_col	= $i_as.'.publish_down';
				$order_dir	= 'ASC';
				break;
			case 'alpha':
				$order_col	= $i_as.'.title';
				$order_dir	= 'ASC';
				break;
			case 'ralpha': case 'alpharev': /* 2nd is for module (PARAMETER FORM ELEMENT: fcordering) */
				$order_col	= $i_as.'.title';
				$order_dir	= 'DESC';
				break;
			case 'author':
				$order_col	= 'u.name';
				$order_dir	= 'ASC';
				break;
			case 'rauthor':
				$order_col	= 'u.name';
				$order_dir	= 'DESC';
				break;
			case 'hits': case 'popular': /* 2nd is for module (PARAMETER FORM ELEMENT: fcordering) */
				$order_col	= $i_as.'.hits';
				$order_dir	= 'DESC';
				break;
			case 'rhits':
				$order_col	= $i_as.'.hits';
				$order_dir	= 'ASC';
				break;
			case 'order': case 'catorder': /* 2nd is for module (PARAMETER FORM ELEMENT: fcordering) */
				$order_col	= $rel_as.'.catid, '.$rel_as.'.ordering ASC, '.$i_as.'.id DESC';
				$order_dir	= '';
				break;

			// SPECIAL case custom field
			case 'field':
				$cf = $sfx == '_2nd' ? 'f2' : 'f';
				$order_type = $params->get('orderbycustomfieldint'.$sfx, 0);
				switch( $order_type )
				{
					case 1:  $order_col = $cf.'.value_integer';  break;   // Integer  // 'CAST('.$cf.'.value AS SIGNED)'
					case 2:  $order_col = $cf.'.value_decimal'; break;    // Decimal  // 'CAST('.$cf.'.value AS DECIMAL(65,15))'
					case 3:  $order_col = $cf.'.value_datetime';  break;  // Date     // 'CAST('.$cf.'.value AS DATETIME)'
					case 4:  $order_col = ($sfx == '_2nd' ? 'file_hits2' : 'file_hits'); break;  // Download hits
					default: $order_col = $cf.'.value'; break;  // Text
				}
				$order_dir = strtoupper($params->get('orderbycustomfielddir'.$sfx, 'ASC')) == 'ASC' ? 'ASC' : 'DESC';
				if ($order_type != 4 && $order_dir=='ASC')
				{
					$order_col = 'ISNULL('.$cf.'.value), ' . $order_col;
				}
				break;

			// NEW ADDED
			case 'random':
				$order_col	= 'RAND()';
				$order_dir	= '';
				break;
			case 'commented':
				$order_col	= 'comments_total';
				$order_dir	= 'DESC';
				break;
			case 'rated':
				$order_col	= 'votes';
				$order_dir	= 'DESC';
				break;
			case 'id':
				$order_col	= $i_as.'.id';
				$order_dir	= 'DESC';
				break;
			case 'rid':
				$order_col	= $i_as.'.id';
				$order_dir	= 'ASC';
				break;
			case 'alias':
				$order_col	= $i_as.'.alias';
				$order_dir	= 'ASC';
				break;
			case 'ralias':
				$order_col	= $i_as.'.alias';
				$order_dir	= 'DESC';
				break;

			case 'default':
			default:
				if (substr($order, 0, 7)=='custom:') {
					$order_parts = preg_split("/:/", $order);
					$_field_id = (int) @ $order_parts[1];
				}
				if (!empty($_field_id) && count($order_parts)==4) {
					$cf = $sfx == '_2nd' ? 'f2' : 'f';
					$order_type = strtolower($order_parts[2]);
					switch( $order_type )
					{
						case 'int':       $order_col = $cf.'.value_integer';  break;   // Integer  // 'CAST('.$cf.'.value AS SIGNED)'
						case 'decimal':   $order_col = $cf.'.value_decimal'; break;    // Decimal  // 'CAST('.$cf.'.value AS DECIMAL(65,15))'
						case 'date':      $order_col = $cf.'.value_datetime'; break;   // Date     // 'CAST('.$cf.'.value AS DATETIME)'
						case 'file_hits': $order_col = ($sfx == '_2nd' ? 'file_hits2' : 'file_hits'); break;  // Download hits
						default:          $order_col = $cf.'.value'; break;
					}
					$order_dir = strtoupper($order_parts[3])=='DESC' ? 'DESC' : 'ASC';
					if ($order_type != 'file_hits' && $order_dir=='ASC')
					{
						$order_col = 'ISNULL('.$cf.'.value), ' . $order_col;
					}
				} else {
					$order_col	= $order_col ? $order_col : $i_as.'.title';
					$order_dir	= $order_dir ? $order_dir : 'ASC';
				}
				break;
		}
		//echo "<br/>".$order_col." ".$order_dir."<br/>";
	}


	/**
	 * Build the order clause of category listings
	 *
	 * @access private
	 * @return string
	 */
	static function buildCatOrderBy(&$params, $order='', $request_var='', $config_param='cat_orderby', $c_as='c', $u_as='u', $default_order_col='', $default_order_dir='')
	{
		// Use global params ordering if parameters were not given
		if (!$params) $params = JComponentHelper::getParams( 'com_flexicontent' );

		// 1. If forced ordering not given, then use ordering parameters from configuration
		if (!$order) {
			$order = $params->get($config_param, 'default');
		}

		// 2. If allowing user ordering override, then get ordering from HTTP request variable
		$order = $request_var && ($request_order = JRequest::getVar($request_var.$sfx)) ? $request_order : $order;

		switch ($order) {
			case 'date':
				$order_col = $c_as.'.created_time';
				$order_dir = 'ASC';
				break;
			case 'rdate':
				$order_col = $c_as.'.created_time';
				$order_dir = 'DESC';
				break;
			case 'modified':
				$order_col = $c_as.'.modified_time';
				$order_dir = 'DESC';
				break;
			case 'alpha':
				$order_col = $c_as.'.title';
				$order_dir = 'ASC';
				break;
			case 'ralpha':
				$order_col = $c_as.'.title';
				$order_dir = 'DESC';
				break;
			case 'author':
				$order_col = $u_as.'.name';
				$order_dir = 'ASC';
				break;
			case 'rauthor':
				$order_col = $u_as.'.name';
				$order_dir = 'DESC';
				break;
			case 'hits':
				$order_col = $c_as.'.hits';
				$order_dir = 'DESC';
				break;
			case 'rhits':
				$order_col = $c_as.'.hits';
				$order_dir = 'ASC';
				break;
			case 'order':
				$order_col = $c_as.'.lft';
				$order_dir = 'ASC';
				break;
			case 'random':
				$order_col	= 'RAND()';
				$order_dir	= '';
				break;
			case 'default' :
			default:
				$order_col = $default_order_col ? $default_order_col : $i_as.'.title';
				$order_dir = $default_order_dir ? $default_order_dir : 'ASC';
				break;
		}

		$orderby 	= ' ORDER BY '.$order_col.' '.$order_dir;
		$orderby .= $order_col!=$c_as.'.title' ? ', '.$c_as.'.title' : '';   // Order by title after default ordering

		return $orderby;
	}


	/**
	 * Check in a record
	 *
	 * @since	1.5
	 */
	static function checkin($tbl, $redirect_url, & $controller)
	{
		$cid = JFactory::getApplication()->input->get('cid', array(0), 'array');
		JArrayHelper::toInteger($cid);

		$user = JFactory::getUser();
		$controller->setRedirect( $redirect_url, '' );

		static $canCheckinRecords = null;
		if ($canCheckinRecords === null) {
			$canCheckinRecords = $user->authorise('core.admin', 'com_checkin');
		}

		// Only attempt to check the row in if it exists.
		$checked_in = 0;
		$diff_user = array();
		$other_err = array();
		foreach($cid as $pk)
		{
			if (!$pk) continue;
			
			// Get an instance of the row to checkin.
			$table = JTable::getInstance($tbl, '');
			if (!$table->load($pk))
			{
				$other_err .= 'ID: '.$pk. ': '.$table->getError();  //$controller->setError($table->getError());  //return; // false;
				continue;
			}

			// Record check-in is allowed if either (a) current user has Global Checkin privilege OR (b) record checked out by current user
			if (!$table->checked_out) continue;
			
			if ( !$canCheckinRecords && $table->checked_out != $user->id )
			{
				$diff_user[] = $pk;  //$controller->setError(JText::_( 'FLEXI_RECORD_CHECKED_OUT_DIFF_USER'));  //return; // false;
				continue;
			}
			
			// Attempt to check the row in.
			if ( !$table->checkin($pk) )
			{
				if (count($other_err) < 3)  $other_err[] = 'ID: '.$pk. ': '.$table->getError();  //$controller->setError($table->getError());  //return; // false;
				continue;
			}
			$checked_in++;
		}
		
		$msg = JText::sprintf('FLEXI_RECORD_CHECKED_IN_SUCCESSFULLY', $checked_in);
		if (count($diff_user))  $msg .= '<br/><br/>IDs: '.implode(', ', $diff_user).' -- '.JText::_( 'FLEXI_RECORD_CHECKED_OUT_DIFF_USER');
		if (count($other_err))  $msg .= '<br/><br/>'.implode('<br/> ', $other_err);
		
		$controller->setRedirect( $redirect_url, $msg, ($other_err ? 'error' : 'message') );
	}
	
	
	/**
	 * Return field types grouped or not
	 *
	 * @return array
	 * @since 1.5
	 */
	static function getFieldTypes($group=false, $usage=false, $published=false)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT plg.element AS field_type, plg.name as title'
			.($usage ? ', count(f.id) as assigned' : '')
			.' FROM #__extensions AS plg'
			.($usage ? ' LEFT JOIN #__flexicontent_fields AS f ON (plg.element = f.field_type AND f.iscore=0)' : '')
			.' WHERE '.($published ? 'plg.enabled=1' : '1')
			.'  AND plg.`type` = ' . $db->Quote('plugin')
			.'  AND plg.`folder` = ' . $db->Quote('flexicontent_fields')
			.'  AND plg.`element` <> ' . $db->Quote('core')
			.($usage ? ' GROUP BY plg.element' : '')
			.' ORDER BY title ASC'
			;
		
		$db->setQuery($query);
		$field_types = $db->loadObjectList('field_type');
		
		foreach($field_types as $field_type) {
			$field_type->friendly = preg_replace("/FLEXIcontent[ \t]*-[ \t]*/i", "", $field_type->title);
		}
		if (!$group) return $field_types;
		
		$grps = array(
			JText::_('FLEXI_SELECTION_FIELDS')          => array('radio', 'radioimage', 'checkbox', 'checkboximage', 'select', 'selectmultiple'),
			JText::_('FLEXI_SINGLE_PROP_FIELDS')        => array('date', 'text', 'textarea', 'textselect'),
			JText::_('FLEXI_MULTIPLE_PROP_FIELDS')      => array('weblink', 'email', 'extendedweblink', 'phonenumbers', 'termlist'),
			JText::_('FLEXI_MEDIA_MINI_APPS_FIELDS')    => array('file', 'image', 'minigallery', 'sharedmedia', 'addressint'),
			JText::_('FLEXI_ITEM_FORM_FIELDS')          => array('fieldgroup', 'account_via_submit', 'groupmarker', 'coreprops'),
			JText::_('FLEXI_DISPLAY_MANAGEMENT_FIELDS') => array('toolbar', 'fcloadmodule', 'fcpagenav', 'linkslist', 'authoritems', 'jprofile'),
			JText::_('FLEXI_ITEM_RELATION_FIELDS')      => array('relation', 'relation_reverse', 'autorelationfilters')
		);
		foreach($grps as $grpname => $field_type_arr)
		{
			$field_types_grp[$grpname] = array();
			foreach($field_type_arr as $field_type)
			{
				if ( !empty($field_types[$field_type]) ) {
					$field_types_grp[$grpname][$field_type] = $field_types[$field_type];
				}
				unset($field_types[$field_type]);
			}
		}
		// Remaining fields
		$field_types_grp['3rd-Party / Other Fields'] = $field_types;
		
		return $field_types_grp;
	}
	
	
	/**
	 * Method to get data/parameters of thie given or all types
	 *
	 * @access public
	 * @return object
	 */
	static function getTypeData($contenttypes_list=false)
	{
		static $cached = null;
		if ( isset($cached[$contenttypes_list]) ) return $cached[$contenttypes_list];
		
		// Retrieve item's Content Type parameters
		$db = JFactory::getDBO();
		$query = 'SELECT * '
				. ' FROM #__flexicontent_types AS t'
				. ($contenttypes_list ? ' WHERE id IN('.$contenttypes_list.')' : '')
				;
		$db->setQuery($query);
		$types = $db->loadObjectList('id');
		foreach ($types as $type) $type->params = new JRegistry($type->attribs);
		
		$cached[$contenttypes_list] = $types;
		return $types;
	}
	
	
	static function getOriginalContentItemids($_items, $ids=null)
	{
		if (empty($ids) && empty($_items)) return array();
		
		if (is_array($_items))
			$items = & $_items;
		else
			$items = array( & $_items );
		
		if (empty($ids))
		{
			$ids = array();
			foreach($items as $item) $ids[] = $item->id;
		}
		
		// Get associated translations
		$db = JFactory::getDBO();
		$query = 'SELECT a.id as id, k.id as original_id'
			. ' FROM #__associations AS a'
			. ' JOIN #__associations AS k ON a.`key`=k.`key`'
			. ' JOIN #__content AS i ON i.id = k.id AND i.language = '. $db->Quote(flexicontent_html::getSiteDefaultLang())
			. ' WHERE a.id IN ('. implode(',', $ids) .') AND a.context = "com_content.item"';
		$db->setQuery($query);
		$assoc_keys = $db->loadObjectList('id');
		
		if (!empty($items))
		{
			foreach($items as $item) $item->lang_parent_id = isset($assoc_keys[$item->id]) ? $assoc_keys[$item->id]->original_id : $item->id;
		}
		else
			return $assoc_keys;
	}
	
		
	static function getLangAssocs($ids)
	{
		$db = JFactory::getDBO();
		$query = 'SELECT a.id as item_id, i.id as id, i.title, i.created, i.modified, i.language as language, i.language as lang'
			. ' FROM #__associations AS a'
			. ' JOIN #__associations AS k ON a.`key`=k.`key`'
			. ' JOIN #__content AS i ON i.id = k.id'
			. ' WHERE a.id IN ('. implode(',', $ids) .') AND a.context = "com_content.item"';
		$db->setQuery($query);
		$associations = $db->loadObjectList();
		
		$translations = array();
		foreach ($associations as $assoc)
		{
			$assoc->shortcode = strpos($assoc->language,'-')  ?  substr($assoc->language, 0, strpos($assoc->language,'-'))  :  $assoc->language;
			$translations[$assoc->item_id][] = $assoc;
		}
		
		return $translations;
	}

	/**
	 * Method to save language associations
	 *
	 * @return  boolean True if successful
	 */
	static function saveAssociations(&$item, &$data, $context)
	{
		$assoc = flexicontent_db::useAssociations();
		if (!$assoc) return true;
		
		
		// **********************************
		// Prepare / check associations array
		// **********************************
		
		// Unset empty associations from associations array, to avoid save them in the associations table
		$associations = isset($data['associations']) ? $data['associations'] : array();
		foreach ($associations as $tag => $id)
		{
			if (empty($id)) unset($associations[$tag]);
		}
		
		// Raise notice that associations should be empty if language of current item is '*' (ALL)
		$all_language = $item->language == '*';
		if ($all_language && !empty($associations))
		{
			JError::raiseNotice(403, JText::_('FLEXI_ERROR_ALL_LANGUAGE_ASSOCIATED'));
		}
		
		// Make sure that current item id, is the association id of the language of the current item
		$associations[$item->language] = $item->id;
		
		// Make sure associations ids are integers
		JArrayHelper::toInteger($associations);
		
		
		// ***********************
		// Delete old associations
		// ***********************
		
		$db = JFactory::getDbo();
		$query = $db->getQuery(true)
			->delete('#__associations')
			->where($db->quoteName('context') . ' = ' . $db->quote($context.'.item'))
			->where($db->quoteName('id') . ' IN (' . implode(',', $associations) . ')');
		$db->setQuery($query);
		$db->execute();
		
		if ($error = $db->getErrorMsg())
		{
			$this->setError($error);
			return false;
		}
		
		
		// ********************
		// Add new associations
		// ********************
		
		// Only add language associations if item language is not '*' (ALL)
		if ($all_language || !count($associations)) return true;
		
		$key = md5(json_encode($associations));
		$query->clear()
			->insert('#__associations');
		
		foreach ($associations as $id)
		{
			$query->values($id . ',' . $db->quote($context.'.item') . ',' . $db->quote($key));
		}
		
		$db->setQuery($query);
		$db->execute();

		if ($error = $db->getErrorMsg())
		{
			$this->setError($error);
			return false;
		}
		return true;
	}
	
	
	/**
	 * Method to determine if J3.1+ associations should be used
	 *
	 * @return  boolean True if using J3 associations; false otherwise.
	 */
	static function useAssociations()
	{
		static $assoc = null;

		if (!is_null($assoc))
		{
			return $assoc;
		}

		$app = JFactory::getApplication();

		$assoc = FLEXI_J30GE && JLanguageAssociations::isEnabled();
		$component = 'com_flexicontent';
		$cname = str_replace('com_', '', $component);
		$j3x_assocs = true;
		
		if (!$assoc || !$component || !$cname || !$j3x_assocs)
		{
			$assoc = false;
		}
		else
		{
			$hname = $cname . 'HelperAssociation';
			JLoader::register($hname, JPATH_SITE . '/components/' . $component . '/helpers/association.php');

			$assoc = class_exists($hname) && !empty($hname::$category_association);
		}
		
		return $assoc;
	}
}