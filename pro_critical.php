<?php
/*----------------------------------------------------------------------------------|  www.vdm.io  |----/
				Gstes Co. 
/-------------------------------------------------------------------------------------------------------/

	@version		1.0.0
	@build			11th ноября, 2019
	@created		11th ноября, 2019
	@package		ForPlugins
	@subpackage		pro_critical.php
	@author			Nikolaychuk Oleg <http://nobd.ml>	
	@copyright		Copyright (C) 2015. All Rights Reserved
	@license		GNU/GPL Version 2 or later - http://www.gnu.org/licenses/gpl-2.0.html
  ____  _____  _____  __  __  __      __       ___  _____  __  __  ____  _____  _  _  ____  _  _  ____ 
 (_  _)(  _  )(  _  )(  \/  )(  )    /__\     / __)(  _  )(  \/  )(  _ \(  _  )( \( )( ___)( \( )(_  _)
.-_)(   )(_)(  )(_)(  )    (  )(__  /(__)\   ( (__  )(_)(  )    (  )___/ )(_)(  )  (  )__)  )  (   )(  
\____) (_____)(_____)(_/\/\_)(____)(__)(__)   \___)(_____)(_/\/\_)(__)  (_____)(_)\_)(____)(_)\_) (__) 

/------------------------------------------------------------------------------------------------------*/

// No direct access to this file
defined('_JEXEC') or die('Restricted access');


use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Plugin\CMSPlugin;

/**
 * System - Pro_critical plugin.
 *
 * @package   Pro_critical
 * @since     1.0.4
 */
class PlgSystemPro_critical extends CMSPlugin
{
		/**
		 * @since 3.7
		 * @var CMSApplication
		 */
		private $app ;
		/**
		 * Экземпляр основного хелпера
		 * @since 3.7
		 * @var Plg\Pro_critical\Helper
		 */
		public $Helper;
		/**
		 * Принудительное отключение в случае ошибки
		 * @since version
		 * @var bool
		 */
		private $SLEEP = false ;
		
		/**
		 * Constructor.
		 *
		 * @param   object  &$subject  The object to observe.
		 * @param   array    $config   An optional associative array of configuration settings.
		 *
		 * @throws \Exception
		 * @since   3.7
		 */
		public function __construct ( &$subject , $config )
		{
			parent::__construct( $subject , $config );
			$this->app = JFactory::getApplication();

		}
		
		/**
		 * Initialise the application.
		 * Trigger the onAfterInitialise event.
		 * @return  void
		 * @throws Exception
		 * @since   3.2
		 */
		public function onAfterInitialise ()
		{
			
			JLoader::registerNamespace( 'Plg\Pro_critical' , JPATH_PLUGINS . '/system/pro_critical/Helpers' , $reset = false , $prepend = false , $type = 'psr4' );
			JLoader::registerNamespace( 'GNZ11' , JPATH_LIBRARIES . '/GNZ11' , $reset = false , $prepend = false , $type = 'psr4' );
			
			try
			{
				$this->Helper = \Plg\Pro_critical\Helper::instance( $this->params );
			}
			catch( Exception $e )
			{
				$this->SLEEP = true;

			}
		}
		
		/**
		 * Route the application.
		 * Trigger the onAfterRoute event.
		 * @return bool
		 * @since   3.2
		 */
		public function onAfterRoute ()
		{
			if( $this->SLEEP ) return false ; #END IF
			
			return true;
		}
		
		/**
		 * Перед созданием HEAD
		 * @return bool
		 * @throws Exception
		 * @throws Throwable
		 * @since     3.8
		 * @copyright 06.12.18
		 * @author    Gartes
		 */
		public function onBeforeCompileHead ()
		{
			if( $this->SLEEP ) return false ; #END IF
			
			$this->Helper->BeforeCompileHead();
			
			return true;
		}
		
		/**
		 * Trigger the onBeforeRender event.
		 * Рендеринг - это процесс вставки буферов документов в шаблон.
		 * заполнители, извлекающие данные из документа и помещающие их в
		 * буфер ответа приложения.
		 *
		 * @since 3.2
		 */
		public function onBeforeRender(){
		
		}
		
		/**
		 * Trigger the onAfterRender event.
		 *
		 * @return bool
		 * @throws Exception
		 * @since   3.2
		 */
		public function onAfterRender ()
		{
			
			if( $this->SLEEP ) return false ; #END IF
			# Если Админ Панель
			if( $this->app->isClient( 'administrator' ) ) return true; #END IF
			
			
			$this->Helper->AfterRender();
			
			// Access to plugin parameters
			// $sample = $this->params->get( 'sample' , '42' );
			
			return true;
		}
		
		
		
		/**
		 * Trigger the onAfterCompress event.
		 * Если в конфигурации включено сжатие gzip и сервер совместим.
		 * @return bool
		 * @since   3.2
		 */
		public function onAfterCompress ()
		{
			if( $this->SLEEP ) return false ; #END IF
			
			return true;
		}
		
		/**
		 * Trigger the onAfterRespond event.
		 * onAfterRespond.
		 * После ответа приложения клиенту перед закрытием приложения
		 * @return bool
		 * @since   1.7.3
		 *
		 */
		public function onAfterRespond ()
		{
			if( $this->SLEEP ) return false ; #END IF
			die(__FILE__ .' '. __LINE__ );
			return true;
		}
		
		/**
		 * Точка входа Ajax
		 *
		 * @since   3.2
		 */
		public function onAjaxPro_critical ()
		{
			$this->Helper->onAjax();
			
		}
}
