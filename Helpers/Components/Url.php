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
			return self::$UrlId = self::get() ; #END IF ;
		}
		
		/**
		 * Получить Url Id из справочника компонента Pro_critical
		 * @return mixed
		 * @throws \Exception
		 * @since 3.9
		 */
		private function get(){
			$current = \JUri::getInstance( )->toString();
			$id = \Pro_criticalHelper::getVar( 'url' , $current , 'url_page' );
			if( $id ) return  $id ; #END IF
			#Добавим новый Url
			return self::$UrlId = self::setUrl( $current ) ;
		}
		
		/**
		 * Добавить текущий URL в справочник URL
		 *
		 * @param $current
		 *
		 * @return \mix
		 * @throws \Exception
		 * @since 3.9
		 */
		private static function setUrl ($current)
		{
			$data['url_page'] =   $current ;
			# Подлючене модели
			$model = \Pro_criticalHelper::getModel( 'url' , JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
			if( $model->save($data) )
			{
				$id = \Pro_criticalHelper::getVar( 'url' , $current , 'url_page' );
			}else{
				throw new \Exception('Error! при создании значения в справочнике Url.');
			}#END IF
			/* var int */
			return  $id ;
			
		}
		
		
		
	}