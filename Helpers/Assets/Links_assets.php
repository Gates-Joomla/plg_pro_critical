<?php
	/**
	 * @package     Plg\Pro_critical\Helpers\Assets
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	use Joomla\CMS\Uri\Uri;
	
	
	/**
	 * Обработка ссылок на ресурсы
	 * @since       version
	 * @package     Plg\Pro_critical\Helpers\Assets
	 *
	 */
	class Links_assets
	{
		
		public static $instance;
		/**
		 * Медиа-версия для файлов по умолчанию
		 * @since 3.9
		 * @var string
		 */
		private $MediaVersion ;
		
		/**
		 * helper constructor.
		 * @throws \Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$doc = \Joomla\CMS\Factory::getDocument();
			$this->MediaVersion = $doc->getMediaVersion() ; 
			
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return Links_assets
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
		
		
		
		
		
		
		
		
		
		
		
		
	}
	
	
	
	
	
	
	
	