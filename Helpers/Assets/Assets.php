<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	
	use Throwable;
	use Exception;
	use JFactory;
	use JDate;
	
	class Assets
	{
		public static $paramsComponent = null ;
		
		
		
		/**
		 * Assets constructor.
		 */
		public function __construct ()
		{
		
		}
		
		
		
		
		
		/**
		 * Получить параметры копонента com_pro_critical
		 * @return array|null
		 * @since 3.9
		 */
		public static function getParamsComponent ()
		{
			if( !self::$paramsComponent )
			{
				$comparams             = \JComponentHelper::getParams( 'com_pro_critical' );
				self::$paramsComponent       = $comparams->toArray();
			}#END IF
			
			return self::$paramsComponent;
		}
		
		
		/**
		 * Получить из справочника Ресурсов по hash
		 * @param $HashArr
		 *
		 * @return mixed
		 * @since 3.9
		 */
		public static function getItemsByHash($HashArr , $model , $Merge = false ){
			
			$db = \JFactory::getDbo() ;
			$query = $db->getQuery(true);
			$query->select('*')->from($db->quoteName('#__pro_critical_'.$model ));
			
			$where[] = $db->quoteName('hash') .  'IN ( "' . implode('","' , $HashArr) . '")' ;
			$where[] = $db->quoteName('published') .  '=1' ;
			$query->where($where) ;
			
			$db->setQuery($query);
			
			//			echo 'Query Dump :'.__FILE__ .' Line:'.__LINE__  .$query->dump() ;
			$Items = $db->loadObjectList('hash') ;
			
			if( $Merge )
			{
				$ItemsMerge = array_merge( (array)$Merge , $Items ) ;
				/*echo'<pre>';print_r( $t );echo'</pre>'.__FILE__.' '.__LINE__;
				echo'<pre>';print_r( $Merge );echo'</pre>'.__FILE__.' '.__LINE__;
				die(__FILE__ .' '. __LINE__ );*/
				return $ItemsMerge;
			}#END IF
			
			
			return $Items ;
		}
		
		
		
		
		/**
		 * Добавить в справочник Assets(ресурсы) новые найденные данные
		 *
		 * @param          $link
		 *
		 * @param          $Model
		 * @param   array  $excludeFields
		 *
		 * @return bool
		 *
		 * @since version
		 */
		protected static function addNewLink ( $link , $Model , $excludeFields =[] )
		{
			# Если включена отладка системы - не сохраняем в справочники
			$config = \Joomla\CMS\Factory::getConfig();
			if( $config->get('debug' , 0 ) )
			{
				return true;
			}#END IF
			
			$excludeFields=['err','protocol','absolute_path','created',  'created_by',   'published' ];
			
			if( !count( $link ) ) return true;
			
			$db = JFactory::getDbo();
			$query   = $db->getQuery( true );
			$jdata   = new JDate();
			$now     = $jdata->toSql();
			$userId  = JFactory::getUser()->id;
			
			$columns = [] ;
			$firstElement = reset($link );
			
			foreach( $firstElement as $key => $itemFile )
			{
				if(  in_array( $key , $excludeFields ) )  continue ;  #END IF
				
				$columns[]= $key ;
			}#END FOREACH
			
			$realColumns = $columns ;
			
			$columns[] = 'created_by';
			$columns[] = 'created';
			$columns[] = 'published';
			
			$count_new_field = null ;
			
			foreach( $link as  $itemFile )
			{
				
				if( isset( $itemFile->published ) &&  $itemFile->published ) continue ;  #END IF
				
				
				$valuesArr = [] ;
				foreach( $realColumns as $key   )
				{
					$item = false ;
					if( isset($itemFile->{$key}) )
					{
						$item = $itemFile->{$key} ;
					}#END IF
					$valuesArr[] =  $db->quote( $item ) ;
				}#END FOREACH
				$valuesArr[] = $db->quote( $userId ) ;
				$valuesArr[] = $db->quote( $now ) ;
				$valuesArr[] = $db->quote( 1 ) ;
				
				
				$query->values( implode( "," , $valuesArr) );
				$count_new_field ++ ;
			}//foreach
			
			if( !$count_new_field )
			{
				return true;
			}#END IF
			
			$query->insert( $db->quoteName( '#__pro_critical_'.$Model ) )->columns( $db->quoteName( $columns ) );
			$db->setQuery( $query );
			
//			echo 'Query Dump :'.__FILE__ .' Line:'.__LINE__  .$query->dump() ;
			
			
			try
			{
				// Code that may throw an Exception or Error.
				$db->execute();
			}
			catch( Exception $e )
			{
				// Executed only in PHP 5, will not be reached in PHP 7
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo'<pre>';print_r(  $e );echo'</pre>'.__FILE__.' '.__LINE__;
				die(__FILE__ .' '. __LINE__ );
			}
			catch( Throwable $e )
			{
				// Executed only in PHP 7, will not match in PHP 5
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo '<pre>'; print_r( $e ); echo '</pre>' . __FILE__ . ' ' . __LINE__;
				die( __FILE__ . ' ' . __LINE__ );
			}
			
			return true;
		}
		
		/**
		 * Подготовить данные для сабформы name|value|published
		 *
		 *
		 * @param          $subFormName
		 * @param   array  $attr
		 *
		 * @return bool|false|float|string|null
		 * @since 3.9
		 */
		public static function getSubFormLinksData ( $subFormName , $attr = [] )
		{
			if( count( $attr ) == null ) return null; #END IF
			
			$res = [];
			
			
			$i = 0;
			foreach( $attr as $name => $value )
			{
				$res[ $subFormName . $i ] = [ 'name' => $name , 'value' => $value , 'published' => true ];
				$i++;
			}#END FOREACH
			
			return json_encode( $res );
		}
		
		/**
		 * Слить json строку с параметрами объекта из спровочника ...
		 * @param $subFormName string - Имя параметра в котором находится стр. json с дфнными
		 * @param $Link object - Параметры из справочника ссылок или тегов
		 *
		 * @return mixed
		 * @since 3.9
		 */
		public static function mergeSubFormLinksParams( $subFormName , $Link )
		{
			$subFormParams = json_decode( $Link->{$subFormName} );
			
			if( empty( $subFormParams ) ) return $Link ;  #END IF
			
			foreach( $subFormParams as $subFormParam )
			{
				if( !$subFormParam->published ) continue ; #END IF
				$name = $subFormParam->name ;
				$value = $subFormParam->value ;
				$Link->{$name} =$value ;
			}#END FOREACH
			return $Link ;
		}
		
	}