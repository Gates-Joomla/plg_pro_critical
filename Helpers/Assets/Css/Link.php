<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\Css;
	
	use Plg\Pro_critical\Helpers\Assets\Links;
	
	/**
	 * Class Link - Обработка ссылок на Css файлы
	 * @since 3.9
	 *@package Plg\Pro_critical\Helpers\Assets\Css
	 */
	class Link extends Links
	{
		
		
		
		
		/**
		 * Подготовить ссылку к загрузи
		 * @param $Link
		 *
		 * @return mixed
		 *
		 * @since version
		 */
		public static function prepareLinkData ( $Link ){
			
			$MediaVersion = self::getMediaVersion()  ;
			
			$Link->href = Links::prepareLinkData($Link);
			
			# id Revision
			if( isset( $Link->ver_type ) && $Link->ver_type && !empty( $Link->revision_id ) )
			{
				$Link->href .= '?i=' . $Link->revision_id;
			}else{
				$Link->href .= '?i=' . $MediaVersion ;
			}#END IF
			
			
			
			
			if ( isset($Link->params_query) && $Link->params_query ) {
				
				$i = null ;
				$queryStr = null ;
				$params_query = json_decode( $Link->params_query );
				foreach( $params_query as $query )
				{
					if( $query->value == $MediaVersion || $query->name == $MediaVersion ) continue ; #END IF
					
					if( isset($query->published) && !$query->published ) continue ;
					
					$queryStr .= !$i ?'':'&' ;
					$queryStr .= $query->name. ( !empty($query->value)?'='.$query->value:'' ) ;
					$i++;
				}#END FOREACH
				
				$Link->href .= ( !empty($queryStr) ? '&' . $queryStr : null ) ;
				
			}
			
			$dataLink = $Link ;
			return $dataLink ;
		}
	
	
	}