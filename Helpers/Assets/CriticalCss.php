<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	
	
	use Plg\Pro_critical\Components\Component;
	use Plg\Pro_critical\Components\Url;
	use Plg\Pro_critical\Helpers\Assets\CriticalCss\Script;
	
	class CriticalCss
	{
		private $app;
		public static $instance;
		private static $CriticalCss ;
		private static $Update = false ;
		/**
		 * @var int - Таймаут для страницы между созданием критических стилей (секунды)
		 * @since 3.9
		 */
		private static $LockTimeLeft = 1 ;
		
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
			if( self::$CriticalCss ) return self::$CriticalCss; #END IF
			
			
			# Получить объект CriticalCss
			if( self::$CriticalCss = $this->getItems() ) return self::$CriticalCss ; #END IF
			# Создать Новый CriticalCss
			self::$CriticalCss = $this->addNewCriticalCss();
			
			return self::$CriticalCss ;
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
			// echo $query->dump();
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
			$Component = Component::instance();
			
			$data['option'] = $Component->getOptionId();
			$data['view']   = $Component->getViewId();
			$data['pro_critical_url_id']   = Url::instance()->getId() ;
			
			$data['checked_out']   = true ;
			$data['checked_out_time']   = new \JDate('now +'.self::$LockTimeLeft.' seconds');
			
			# Подлючене модели
			$model = \Pro_criticalHelper::getModel( 'css' , JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
			
			if( $model->save($data) )
			{
				self::$Update = true ;
				return  $model->get('state')->{'css.id'} ;
				
			}else{
				throw new \Exception('Error! при создании значения в справочнике CriticalCss .');
			}#END IF
		
		}
		
		/**
		 * проверить если нужно обновление отправить скриты
		 * @throws \Exception
		 * @since 3.9
		 */
		public static function ifUpdate(){
			if( self::$Update )
			{
				
				Script::add( self::$CriticalCss );
			}#END IF
		
		}
		
		public function onAjaxApiCritical(){
			die(__FILE__ .' '. __LINE__ );
		}
		
		
	}