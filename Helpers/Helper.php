<?php
	
	namespace Plg\Pro_critical;
	use Joomla\Registry\Registry;
	use JFactory;
	use JLoader;
	
	use JUri;
	use JSession;
	use JText;
	use JResponseJson;
	use Exception;
	use Joomla\CMS\Component\ComponentHelper;
	use Throwable;
	
	// No direct access to this file
	defined( '_JEXEC' ) or die( 'Restricted access' );
	
	/**
	 * @since       3.9
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 * @package     Plg\Pro_critical
	 */
	class Helper
	{
		public static $instance;
		
		private $app;
		
		private $params;
		private $paramsComponent;
		private $GNZ11_js;
		
		
		/**
		 * Имя компонента для вызова модели
		 * @var string
		 * @since 3.9
		 */
		private static $component = 'pro_critical';
		private static $prefix = 'pro_critical' . 'Model';
		
		/**
		 * helper constructor.
		 *
		 * @param $params
		 *
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct ( $params  )
		{
			
			$this->app = JFactory::getApplication();
			$this->params = $params ;
			
			$Component = ComponentHelper::getComponent('com_pro_critical', $strict = true);
			
			if(!$Component->id ){
				$mes = 'Для правильной работы <b>плагина Pro Critical</b> - должен быть установлен и включен <b>компонент Pro Critical</b>' ;
				if( $this->app->input->get('format' , 'html' , 'STRING') == 'json' ) {
					$mes ='';
				} #END IF
				
				$this->app->enqueueMessage($mes , 'warning');
				throw new Exception( $mes , 500 );
			}
			# TODO - добавить сообщение для фронта
			#Если включен ркжим отладки
			if( JDEBUG )
			{
				// throw new Exception( null , 500 );
			}#END IF
			
			
			
			
			$this->paramsComponent = ComponentHelper::getParams( 'com_pro_critical' );
			$this->paramsComponent->set('plugin_param' , $this->params ) ;
			JLoader::register( 'Pro_criticalHelper', JPATH_ADMINISTRATOR . '/components/com_pro_critical/helpers/pro_critical.php' );
			JLoader::registerNamespace('Plg\Pro_critical\Helpers\Assets',JPATH_PLUGINS.'/system/pro_critical/Helpers/Assets',$reset=false,$prepend=false,$type='psr4');
			JLoader::registerNamespace( 'Com_pro_critical\Helpers' ,
				JPATH_ADMINISTRATOR . '/components/com_pro_critical/com_pro_critical/helpers' ,
				$reset = false , $prepend = false , $type = 'psr4' );
			
			
			
			 return $this;
		}#END FN
	
		/**
		 * @param   Registry  $params
		 *
		 * @return helper
		 * @throws Exception
		 * @since 3.9
		 */
		public static function instance ( $params = null )
		{
			if( !$params ) { $params = new \Joomla\Registry\Registry; }#END IF
			if( self::$instance === null )
			{
				self::$instance = new self(  $params  );
			}
			
			return self::$instance;
		}#END FN
		
		
		
		/**К ссылке этого файла будет добавлен атрибут async
		 * Перед созданием HEAD
		 *
		 * @throws Exception
		 * @since version
		 */
		public function BeforeCompileHead(){
			$doc = JFactory::getDocument();
			
			
			$DefaultLanguage = \Plg\Pro_critical\Helper_site::getDefaultLanguage();
			$languages = \JLanguageHelper::getLanguages('lang_code');
			$doc->addScriptOptions('langSef'  , $languages[$DefaultLanguage]->sef ) ;
			
			
			$menu = \JFactory::getApplication()->getMenu();
			$active = $menu->getActive();
			$doc->addScriptOptions('itemId'  , (!empty($active) ? $active->id : false) ) ;
			
			
			
			$Component_virtuemart = ComponentHelper::getComponent('com_virtuemart', $strict = true);
			if(!$Component_virtuemart->id ){
				if (!class_exists( 'VmConfig' )) require(JPATH_ROOT .'/administrator/components/com_virtuemart/helpers/config.php');
				\VmConfig::loadConfig();
			}
			
			
			
			
			
			# instance GNZ11
			# Утановить настройки библионтеки GNZ11
			$this->GNZ11_js =  \GNZ11\Core\Js::instance( $this->paramsComponent  );
			
			
			$doc->addScriptOptions('siteUrl'  , JUri::root() ) ;
			$doc->addScriptOptions('isClient'  , $this->app->isClient( 'administrator' )  ) ;
			$doc->addScriptOptions('csrf.token'  , JSession::getFormToken()  ) ;
//			$doc->addScriptOptions('csrf.token'  , JSession::getFormToken()  ) ;
			
			
			# Только для FRONT
			if( !$this->app->isClient( 'administrator' ) ){
				# Получить Id компонента в справочнике компонентов com Pro Critical
				$Component = \Plg\Pro_critical\Components\Component::instance();
				$option_id = $Component->getOptionId();
				$view_id = $Component->getViewId();
			}
		 
			
			
			
			# Только для ADMIN
			if( !$this->app->isClient( 'administrator' ) ) return ;
			
			
			
			# установка ресурсов для админ панели
			\Com_pro_critical\Helpers\helper::settingsAdminViews() ;
			
			
			
			/*if( $this->app->input->get('option') == 'com_pro_critical' )
			{
			
			}#END IF*/
		}
		
		/**
		 * Только для FrontEnd
		 *
		 * @throws Exception
		 * @since version
		 */
		public function AfterRender(){
			
			$HelpersCss = Helpers\Assets\Css::instance();
			
			try
			{
				// Code that may throw an Exception or Error.
				# Найти и извлечь все ссылки на CSS файлы и теги стили
				$HelpersCss->getFileList();
				
				# Установить в HTML ссылки на Css файлы и стили
				$HelpersCss->insertStylesIntoDocument();
			}
			catch( Exception $e )
			{
				// Executed only in PHP 5, will not be reached in PHP 7
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo '<pre>'; print_r( $e );
				echo '</pre>' . __FILE__ . ' ' . __LINE__;
				die( __FILE__ . ' ' . __LINE__ );
			}
			catch( Throwable $e )
			{
				// Executed only in PHP 7, will not match in PHP 5
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo '<pre>';
				print_r( $e );
				echo '</pre>' . __FILE__ . ' ' . __LINE__;
				die( __FILE__ . ' ' . __LINE__ );
			}
			
			$HelpersJs = Helpers\Assets\Js::instance();
			# Извлечь из страницы в скриты и ссылку на Js файлы
			# добавить новые найденые в справочники
			$HelpersJs->getListJs();
			
			
			
			
			
		}
		
		/**
		 * Точка входа Ajax
		 *
		 * @since version
		 */
		public function onAjax(){
			
			# Проверить Token
			if(!JSession::checkToken('get')) exit('Err check Token');
			
			$dataModel = $this->app->input->get('model' , false , 'RAW' );
			
			if( !$dataModel )
			{
				echo new JResponseJson( false , JText::_('MODEL ERROR'), true);
				$this->app->close();
			}#END IF
			
			$inputTask = $this->app->input->get('task' , false , 'STRING' );
			
			$model = '\Plg\Pro_critical'.$dataModel ;
			$obj = new $model();
			
			$res = $obj->{$inputTask}();
			
			if(  !$res )
			{
				echo new JResponseJson( false , JText::sprintf('METHOD %s:%s ERROR' , $model , $inputTask ), true);
				$this->app->close();
			}#END IF
			echo new JResponseJson( $res );
			$this->app->close();
			
			
			
			
		}
		
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	