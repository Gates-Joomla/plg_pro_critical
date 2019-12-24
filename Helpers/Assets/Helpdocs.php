<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	
	class Helpdocs
	{
		
		/**
		 * HelpDocs constructor.
		 * @since 3.9
		 */
		public function __construct ()
		{
			return $this ;
		}
		
		public function getHelpFile( $file ){
			$db = \JFactory::getDbo();
			$query = $db->getQuery( true ) ;
			$query->select('*')->from($db->quoteName('#__pro_critical_help_document_data'));
			$where = [
				$db->quoteName('file') .'='. $db->quote($file) ,
			];
			$query->where($where);
			$db->setQuery($query);
			$res = $db->loadObject();
			
			return $res ;
		}
		
	}