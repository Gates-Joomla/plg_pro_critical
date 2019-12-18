<?php
	
	
	namespace Plg\Pro_critical\Components;
	
	
	use JFactory;
	use Exception;
	use Pro_criticalHelper;
	
	class View
	{
		private $app;
		public static $instance;
		
		public static $view_id;
		/**
		 * @var Option
		 * @since 3.9
		 */
		public $Option;
		
		/**
		 * View constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = JFactory::getApplication();
			$this->Option = Option::instance();
			
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return View
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
		 * @param   null  $View
		 *
		 * @return mixed
		 * @throws Exception
		 * @since 3.9
		 */
		public function getViewId( $View = null ){
			if( self::$view_id ) return self::$view_id; #END IF
			
			
			# TODO -разобратся с Router портит каноникал в com_SEF404
			$uri = \JUri::getInstance( );
			$router            = $this->app->getRouter();
			$Router_Data = $router->parse( $uri );
			
			unset($Router_Data['pro_critical']) ;
			
			
			$Hash = self::getHashView($Router_Data);
			
			# Получить view Id из справочника компонента Pro_critical
			self::$view_id = Pro_criticalHelper::getVar( 'directory_views' , $Hash , 'hash' );
			if( self::$view_id ) return self::$view_id ; #END IF
			
			# Добавить новое представление
			self::$view_id = $this->addNew($Router_Data , $Hash  ) ;
			
			return self::$view_id ;
		}
		
		/**
		 * @param array $Router_Data
		 * @param string $Hash
		 *
		 * @return \mix
		 * @throws Exception
		 * @since 3.9
		 */
		private function addNew($Router_Data , $Hash  ){
			
			$data = [] ;
			$data['id_component'] = $this->Option->getOptionId() ;
			$data['view_component'] = $Router_Data['view'] ;
			$data['value_view'] = $Router_Data['view'] ;
			$data['hash'] = $Hash ;
			$data['query_params'] = [] ;
			
			unset( $Router_Data['option'] );
			unset( $Router_Data['view'] );
			
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
			
			# Подлючене модели
			$model = Pro_criticalHelper::getModel( 'directory_views' , JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
			
			if( $model->save($data) )
			{
				return Pro_criticalHelper::getVar( 'directory_views' , '' . $Hash , 'hash' );
			}else{
				throw new Exception('Error! при создании значения в справочнике представлений.');
			}#END IF
			
		}
		
		
		
		
		
		
		/**
		 * @throws Exception
		 * @since 3.9
		 */
		public static function getHashView ($Router_Data){
			
			return   md5(  json_encode($Router_Data)  )  ;
			
		}
		
		
	}