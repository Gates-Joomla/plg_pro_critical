<?php
	
	namespace Plg\Pro_critical\Components;
	
	use Exception;
	use Joomla\CMS\Component\ComponentHelper;
	use Pro_criticalHelper;
	use Throwable;
	
	
	class Component
	{
		private static $option_id;
		private static $view_id;
		private $app;
		public static $instance;
		
		/**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = \JFactory::getApplication();
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return Component
		 * @throws Exception
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
		
		/**
		 * @param   null  $option
		 *
		 * @return mixed
		 * @throws Exception
		 * @since 3.9
		 */
		public function getOptionId ( $option = null  )
		{
			if( self::$option_id ) return self::$option_id; #END IF
			
			$model_Name = 'directory_components';
 
			$model = Pro_criticalHelper::getModel( $model_Name , JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
			
			
			if( !$option ) $option = $this->app->input->get('option' , false ) ; #END IF
			
			# Получить информацию о компоненте
			$Extension =   ComponentHelper::getComponent($option, $strict = false);
			$IdExtension = $Extension->id ;
			
			self::$option_id = Pro_criticalHelper::getVar( $model_Name , '' .  $Extension->id , 'extension_id' );
			
			
			
			if( !self::$option_id )
			{
				# Получить информацию о компоненте
				$ExtensionInfo = Pro_criticalHelper::getExtension( $option ) ;
				# если отсутствует компонент Создаем
				$data = [] ;
				$data['copmonent_name'] = $ExtensionInfo['name'] ;
				$data['value_option'] = $option ;
				$data['extension_id'] = $Extension->id  ;
				if( !$model->save($data)  )
				{
					throw new \Exception('Error! при создании значения в справочнике компонентов.');
				}#END IF
			}#END IF
			self::$option_id = Pro_criticalHelper::getVar( $model_Name , '' . $IdExtension , 'extension_id' );
			
			$Item = $model->getItem( self::$option_id ) ;
			if( $Item->published != 1 )
			{
				$this->app->enqueueMessage('Справочник компонентов: Значене ' .$Item->copmonent_name . 'не активно!' , 'warning' );
			}#END IF
			
			return self::$option_id;
		
		}#END FN
		
		/**
		 * Получить ID view - из справочника * directory_views *
		 *
		 * @param $View
		 *
		 * @return int - ID View
		 * @throws \Exception
		 * @since 3.9
		 */
		public function getViewId( $View = null ){
			
			if( self::$view_id ) return self::$view_id; #END IF
			
			if( !$View ) $View = $this->app->input->get('view' , false ) ; #END IF
			
			
			$View = mb_strtolower( $View );
			
			
			# TODO - Удаление исключенных параметров запросов - перенести в метод Helper Component
			# получить настройки копанента
			$comparams = \JComponentHelper::getParams( 'com_pro_critical');
			$paramsComponent = $comparams->toArray();
			
			
			# Из глобальных насторек
			# параметры запросов которые игнорировать
			$excluded_request_global = $paramsComponent['excluded_request_global'] ;
			
			# Подлючене модели
			$model_Name = 'directory_views';
			$model = Pro_criticalHelper::getModel( $model_Name , JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
			
			# Получене id компонента из справочника компонентов
			$OptionId = $this->getOptionId();
			
			$Hash = md5( implode( '|' , [$OptionId , $View ] ) ) ;
			self::$view_id = Pro_criticalHelper::getVar( $model_Name , $Hash , 'hash' );
			
			
			# TODO -разобратся с Router портит каноникал в com_SEF404
			$uri = \JUri::getInstance( );
			$router            = $this->app->getRouter();
			$Router_Data = $router->parse( $uri );
			
			
			unset( $Router_Data['option'] );
			unset( $Router_Data['view'] );
			# Удаление исключенных параметров запросов
			foreach( $excluded_request_global as $item )
			{
				$query = $item['query'];
				unset( $Router_Data[$query] );
			}#END FOREACH
			
			
			
			
			$Update = true ;
			if( self::$view_id )
			{
				$Update = false ;
				$Item = $model->getItem( self::$view_id ) ;
				
				if( $Item->published < 1 )
				{
					$this->app->enqueueMessage('Справочник views: Значене ' .$Item->view_component . 'не активно!' , 'warning' );
				}#END IF
				
				foreach( $Item->query_params as $queryParam )
				{
					$param = $queryParam['param_query'] ;
					if( key_exists($param , $Router_Data ) )
					{
						unset( $Router_Data [$param]);
					}#END IF
				}#END FOREACH
				
				# Если есть новые парамтры запроса
				if( count($Router_Data) )
				{
					# Триггер обновления
					$Update = true ;
					
					# Создать массив для subForm
					$i = count($Item->query_params);
					foreach( $Router_Data as $key => $routerDatum )
					{
						$add = [ 'param_query' => $key , 'status' => true , ];
						$Item->query_params['query_params' . $i ] = $add ;
						$i ++ ;
					}#END FOREACH
					unset($i) ;
					
					if( isset($Item->tags) )
					{
						unset( $Item->tags )  ;
					}#END IF
					
					
				}#END IF
			
			}#END IF
			
			
			
			
			# Если настройки для вида отсутствуют в справочнике
			if( !self::$view_id )
			{
				$data = [] ;
				$data['id_component'] = $OptionId ;
				$data['view_component'] = $View ;
				$data['value_view'] = $View ;
				$data['hash'] = $Hash ;
				$data['query_params'] = [] ;
				
				# Автоматическое создание полей для SUB FORMS
				$x = 0 ;
				foreach( $Router_Data as $key => $routerDatumItem )
				{
					$data['query_params']['query_params'.$x] = [
						'param_query' => $key ,
						'status' => 1,
					];
					$x++;
				}#END FOREACH
				unset($x);
				
				# Триггер обновления
				$Update = true ;
				
				$Item = $data ;
				
				# сохраняем данные
				if( $model->save($data) )
				{
					self::$view_id = Pro_criticalHelper::getVar( $model_Name , '' . $Hash , 'hash' );
				}else{
					throw new \Exception('Error! при создании значения в справочнике представлений.');
				}#END IF
				
			}#END IF
			
			if( $Update )
			{
				try
				{
					// Code that may throw an Exception or Error.
					$this->saveOrUpdate( $model , (array) $Item );
				}
				catch( Exception $e )
				{
					// Executed only in PHP 5, will not be reached in PHP 7
					$this->app->enqueueMessage( $e->getMessage() , 'error' );
					return self::$view_id ;
				}
				catch( Throwable $e )
				{
					// Executed only in PHP 7, will not match in PHP 5
					$this->app->enqueueMessage( $e->getMessage() , 'error' );
					return self::$view_id ;
				}
				
				
			}#END IF
			
			
			
			return self::$view_id ;
			
		}#END FN
		
		/**
		 * @param $model
		 * @param $data
		 *
		 * @return bool
		 * @throws Exception
		 * @since 3.9
		 */
		private function saveOrUpdate( $model , $data  ){
			
			$messageErr = 'Error! при создании значения в справочнике представлений.' ;
			
			try
			{
				// Code that may throw an Exception or Error.
				$model->save( $data );
			}
			catch( Exception $e )
			{
				// Executed only in PHP 5, will not be reached in PHP 7
				$this->app->enqueueMessage( $e->getMessage() , 'error');
				throw new \Exception( $messageErr );
			}
			catch( Throwable $e )
			{   // Executed only in PHP 7, will not match in PHP 5
				$this->app->enqueueMessage( $e->getMessage() , 'error');
				throw new \Exception( $messageErr );
			}
			
			# сохраняем данные
			if( $model->save($data) )
			{
				return true ;
				
			}else{
			
			}#END IF
		}
		
		
		
		
	}