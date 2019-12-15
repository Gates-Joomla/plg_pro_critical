<?php
	
	
	namespace Plg\Pro_critical\Components;
	
	
	class Url
	{
		public static $UrlId;
		private $app;
		public static $instance;
		
		/**
		 * helper constructor.
		 * @throws \Exception
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
		 * @return Url
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
		
		/**
		 * @return mixed
		 * @throws \Exception
		 * @since 3.9
		 */
		public function getId(){
			if( self::$UrlId ) return self::$UrlId ; #END IF
			if( self::$UrlId = self::get() ) return self::$UrlId = self::get() ; #END IF ;
			self::$UrlId = self::setUrl() ;
			return self::$UrlId ;
		}
		
		/**
		 * Получить Url Id из справочника компонента Pro_critical
		 * @return mixed
		 * @since 3.9
		 */
		private function get(){
			$uri = \JUri::getInstance( );
			$current = $uri->current();
			return \Pro_criticalHelper::getVar( 'url' , $current , 'url_page' );
		}
		
		
		/**
		 * @param   string  $Url
		 *
		 * @return int id Url
		 * @throws \Exception
		 * @since 3.9
		 */
		private static function setUrl (  )
		{
			$uri = \JUri::getInstance( );
			$data['url_page'] =   $uri->current();
			# Подлючене модели
			$model = \Pro_criticalHelper::getModel( 'url' , JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
			if( $model->save($data) )
			{
				return  $model->get('state')->{'css.id'} ;
				
			}else{
				throw new \Exception('Error! при создании значения в справочнике Url.');
			}#END IF
		}
		
		
	}