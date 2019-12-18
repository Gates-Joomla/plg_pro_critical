<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	
	
	use Plg\Pro_critical\Components\Component;
	use Plg\Pro_critical\Components\Url;
	use Plg\Pro_critical\Helpers\Assets\CriticalCss\Script AS CriticalCssScript;
	use Plg\Pro_critical\Components\Option AS ComponentsOption;
	
	class CriticalCss
	{
		private $app;
		public static $instance;
		/**
		 * @var int - ID текущего Critical Css Object
		 * @since 3.9
		 */
		public static $CurrentCriticalId;
		private static $CriticalCssData ;
		private static $Update = false ;
		/**
		 * @var int - Таймаут для страницы между созданием критических стилей (секунды)
		 * @since 3.9
		 */
		private static $LockTimeLeft = 20 ;
		
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
		 * @return CriticalCss
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
		 * Getters CriticalCss
		 * Event onAfterRender
		 * @throws \Exception
		 * @since 3.9
		 */
		public function getCriticalCss(){
			
			if( self::$CriticalCssData ) return self::$CriticalCssData; #END IF
			# Получить объект CriticalCss
			
			$CriticalCssData = $this->getItems() ;
			if( !$CriticalCssData )
			{
				# Создать Новый CriticalCss
				$CriticalCssID = $this->addNewCriticalCss();
				$CriticalCssData = $this->getItems() ;
			}else{
				$CriticalCssID = $CriticalCssData ['id'] ;
			}#END IF
			
			self::$CurrentCriticalId = $CriticalCssID ;
			self::$CriticalCssData = $CriticalCssData ;
			return self::$CriticalCssData ;
			
			/*echo'<pre>';print_r( self::$CurrentCriticalId );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( self::$CriticalCssData );echo'</pre>'.__FILE__.' '.__LINE__;
			
			
			if( self::$CriticalCssData = $this->getItems() ) {
				self::$CurrentCriticalId = self::$CriticalCssData['id'] ;
				return self::$CriticalCssData ;
			} #END IF
			
			# Создать Новый CriticalCss
			$CriticalCssID = $this->addNewCriticalCss();
			
			echo'<pre>';print_r( $CriticalCssID );echo'</pre>'.__FILE__.' '.__LINE__;
			
//			self::$CriticalCss = $this->addNewCriticalCss();*/
			
			
		
		}
		
		
		
		
		/**
		 * Создать Новый CriticalCss
		 * @throws \Exception
		 * @since 3.9
		 */
		private function getItems ()
		{
			$Component = Component::instance();
			$db        = \JFactory::getDbo();
			
			$where   = [];
			$where[] = $db->quoteName( 'option' ) . '=' . $Component->getOptionId();
			$where[] = $db->quoteName( 'view' ) . '=' . $Component->getViewId();
			
			$query = $db->getQuery( true );
			$query->select( '*' )->from( $db->quoteName( '#__pro_critical_css' ) )->where( $where );
//			echo $query->dump();
			$db->setQuery( $query );
			$res = false ;
			try
			{
				// Code that may throw an Exception or Error.
				$res = $db->loadAssoc();
			}
			catch( \Exception $e )
			{
				// Executed only in PHP 5, will not be reached in PHP 7
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
			}
			catch( \Throwable $e )
			{
				// Executed only in PHP 7, will not match in PHP 5
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo '<pre>';
				print_r( $e );
				echo '</pre>' . __FILE__ . ' ' . __LINE__;
				die( __FILE__ . ' ' . __LINE__ );
			}
			
			# проверить для повторной попытки создать
			if( $res && !$res['critical_css_code'] )
			{
				$this->checkUpdate($res);
				
			}#END IF
			
			return $res ;
		}
		
		/**
		 * Проверка на Lock
		 *
		 * @param $Critical
		 *
		 * @throws \Exception
		 * @since 3.9
		 */
		private function checkUpdate($Critical){
			$now = new \JDate('now');
			$nowUp = new \JDate('now +'.self::$LockTimeLeft.' seconds') ;
			
			
			if( $Critical['checked_out_time'] < $now->toSql() )
			{
				$Critical['checked_out_time']   = $nowUp->toSql();
				# Подлючене модели
				$model = \Pro_criticalHelper::getModel( 'css' , JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
				$model->save( $Critical ) ;
				self::$Update = true ;
			}#END IF
			
		}
		
		/**
		 * Создать нопый объект CriticalCss
		 * @throws \Exception
		 * @since 3.9
		 */
		private function addNewCriticalCss(){
			$PNT = $this->app->input->get('pro_critical' , false , INT ) ;
			if( $PNT ) return #END IF
			
			$Component = Component::instance();
			$Option = ComponentsOption::instance();
			$View = \Plg\Pro_critical\Components\View::instance() ;
			$data['option'] = $Option->getOptionId();
			$data['view']   = $View->getViewId();
			$data['pro_critical_url_id']   = Url::instance()->getId() ;
			
			# TODO - привязать к администратору
			$data['checked_out']   = 2727 ;
			$data['checked_out_time']   = new \JDate('now +'.self::$LockTimeLeft.' seconds');
			
			# Подлючене модели
			$model = \Pro_criticalHelper::getModel( 'css' , JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
			
			if( $model->save($data) )
			{
				self::$Update = true ;
				self::$CurrentCriticalId = $model->get('state')->{'css.id'} ;
				return  self::$CurrentCriticalId ;
				
			}else{
				throw new \Exception('Error! при создании значения в справочнике CriticalCss .');
			}#END IF
		
		}
		
		/**
		 * Проверить если нужно обновление отправить скриты
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function ifUpdate(){
			if( self::$Update )
			{
				CriticalCssScript::add( self::$CriticalCssData );
			}#END IF
		
		}
		
		public function onAjaxApiCritical(){
			die(__FILE__ .' '. __LINE__ );
		}
		
		
	}