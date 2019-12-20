<?php
	
	
	namespace Plg\Pro_critical\Html;
	
	
	use function GuzzleHttp\Promise\task;
	
	class Html
	{
		private $app;
		public static $instance;
		/**
		 * @var int - Id компонента из справочника directory_components
		 * @since 3.9
		 */
		private static $option_id;
		/**
		 * @var int - Id представления из справочника directory_views
		 * @since 3.9
		 */
		private static $view_id;
		
		private static $TaskArr = [
			0 => 'Custom',
			1 => 'Images',
			2 => 'Youtube',
		];
		/**
		 * helper constructor.
		 * @throws \Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = \JFactory::getApplication();
			$Component = \Plg\Pro_critical\Components\Component::instance();
			self::$option_id = $Component->getOptionId();
			self::$view_id   = $Component->getViewId();
			$TasksArr = $this->getHtmlTask();
			$sortTaskArr = $this->filterTask( $TasksArr );
			
			$this->applyTask($sortTaskArr) ;
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return Html
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function instance ( $options = [] )
		{
			if( self::$instance === null )
			{
				self::$instance = new self( $options );
			}
			return self::$instance;
		}#END FN
		
		private function applyTask($sortTaskArr){
			
			echo'<pre>';print_r( $sortTaskArr );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );
			foreach( $sortTaskArr as $item )
			{
				$id_task = $item['type_html_task'] ;
				switch($id_task){
					case 1 :
						
						\Plg\Pro_critical\Html\Tasks\Images::aplly($item);
						break ;
					case 2 :
						\Plg\Pro_critical\Html\Tasks\Youtube::aplly($item);
						
						
						break;
					case 0 :
						$aaa = null ;
						break;
				}
				
				
				
				
				
			}#END FOREACH
			return true ;
		}
		
		/**
		 * Отфильтровать ненужные задачи
		 *
		 * @param $TasksArr
		 *
		 * @return array - отфильтрованные задачи
		 * @since 3.9
		 */
		private function filterTask( $TasksArr){
			$uri = \JUri::getInstance( );
			$router            = $this->app->getRouter();
			$Router_Data = $router->parse( $uri );
			
			
			$sortTaskArr = [] ;
			
			echo'<pre>';print_r($TasksArr  );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );
			
			foreach( $TasksArr as $i=> $item )
			{
				
				
				
				$query_params = $item['query_params'];
				$is = false ;
				foreach( $query_params as $queryParam )
				{
					if( empty( $queryParam->query )  ) {
						$is = true ;
					}else{
						if( isset( $Router_Data[$queryParam->query] ) && $Router_Data[$queryParam->query] == $queryParam->value )
						{
							$is = true ;
							// $routerDataQuery = $Router_Data[$queryParam->query] ;
						}else{
							$is = false ;
						}#END IF
					} #END IF
				}#END FOREACH
				
				if( $is )
				{
					$sortTaskArr[] = $TasksArr[$i]  ;
				}#END IF
			}#END FOREACH
			
			return $sortTaskArr ;
			
		}
		
		/**
		 * Получить все задачи для текущего компонента и вида
		 * @return mixed
		 * @since 3.9
		 */
		private function getHtmlTask(){
			
			$db = \JFactory::getDbo();
			$query = $db->getQuery(true);
			$select = [
				$db->quoteName( 't.*' ),
			];
			
			$query->select($select)
				->from($db->quoteName('#__pro_critical_html_task','t'));
			
			$query->join('LEFT', '#__pro_critical_directory_views AS v ON v.value_view = component_view_id' );
			
			$where = [
				$db->quoteName('t.id_component') .' = '. $db->quote(self::$option_id ),
				$db->quoteName('component_view_id') .' = '. $db->quoteName('value_view' ),
				$db->quoteName('v.id') .' = '. $db->quote(self::$view_id ),
			];
			$query->where($where);
			
			$db->setQuery($query);
			$res = $db->loadAssocList();
			foreach( $res as $i => $resItems )
			{
				$query_params = json_decode( $resItems['query_params'] ) ;
				
				$res[$i]['query_params'] = $query_params ;
			}#END FOREACH
			return $res;
		}
		
		
	}