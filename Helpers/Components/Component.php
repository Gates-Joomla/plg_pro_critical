<?php
	
	namespace Plg\Pro_critical\Components;
	
	use Exception;
	use Joomla\CMS\Component\ComponentHelper;
	use Pro_criticalHelper;
	use Throwable;
	
	class Component
	{
		public static $option_id;
		public static $view_id;
		private $app;
		private $Option ;
		public static $instance;
		
		/**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = \JFactory::getApplication();
			$this->Option = Option::instance();
			$this->View = View::instance();
			
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
		 * Получить Option ID
		 *
		 * @return mixed
		 * @throws Exception
		 * @since 3.9
		 */
		public function getOptionId (){
			return $this->Option->getOptionId();
		}
		
		/**
		 * Получить ID view - из справочника * directory_views *
		 * use event onAfterRender
		 *
		 * @return int - ID View
		 * @throws \Exception
		 * @since 3.9
		 */
		public function getViewId(){
			
			return $this->View->getViewId();
			
			/*
			
			# TODO Разобраться с настройкой представлений - исключения параметров !!!
			# TODO - Удаление исключенных параметров запросов - перенести в метод Helper Component
			# получить настройки копанента
			$comparams = \JComponentHelper::getParams( 'com_pro_critical');
			$paramsComponent = $comparams->toArray();
			# Из глобальных насторек
			# параметры запросов которые игнорировать
			$excluded_request_global = $paramsComponent['excluded_request_global'] ;
			# Удаление исключенных параметров запросов
			foreach( $excluded_request_global as $item )
			{
				$query = $item['query'];
				unset( $Router_Data[$query] );
			}#END FOREACH
			*/
			
			
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