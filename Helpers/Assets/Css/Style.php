<?php
	namespace Plg\Pro_critical\Helpers\Assets\Css;
	
	class Style
	{
		
		public static function prepareStyleData($Style){
			
			
			return $Style->content_min ;
		}
		
		
		/**
		 * Получить из справочника css_style по hash
		 * @param $HashArr
		 *
		 * @return mixed
		 * @since 3.9
		 */
		public static function getItemsByHash($HashArr){
			
			$db = \JFactory::getDbo() ;
			$query = $db->getQuery(true);
			$query->select('*')->from($db->quoteName('#__pro_critical_css_style'));
			
			// $where = explode(',' , $HashArr) ;
			$where[] = $db->quoteName('hash') .  'IN ( "' . implode('","' , $HashArr) . '")' ;
			$where[] = $db->quoteName('published') .  '=1' ;
			$query->where($where) ;
			
			$db->setQuery($query);
			
//			echo 'Query Dump :'.__FILE__ .' Line:'.__LINE__  .$query->dump() ;
			$Items = $db->loadObjectList('hash') ;
			return $Items ;
		}
	}