<?php
	
	namespace Plg\Pro_critical\Html\Libraries;
	
	class LazyLoad
	{
		private $app;
		public static $instance;
		// public static $Link = 'https://cdn.jsdelivr.net/npm/vanilla-lazyload@12.4.0/dist/lazyload.min.js';
		public static $Link = '/libraries/GNZ11/assets/js/modules/gnz11.Lazyload.js';
		
		/**
		 * helper constructor.
		 * @throws \Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = \JFactory::getApplication();
			$this->install();
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return LazyLoad
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
		
		private function install(){
			$dom = new \GNZ11\Document\Dom();
			# Добавить тег ссылка Js в тело документа перед закрывающемся тегом </body>
			$dom::writeDownTag( 'script' , null , ['src' => self::$Link , 'async'=> 1 , 'onload'=>'onLazyIsLoad()'  ] );
			
			/*$script = "
			
			
			";
			$dom::writeDownTag( 'script' , null , ['src' => self::$Link , 'async'=> 1 , 'onload'=>'onLazyIsLoad()'  ] );
			
			*/
			
			
		}
		
		
	}