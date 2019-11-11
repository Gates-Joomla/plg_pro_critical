<?php
	/**
	 * @package     ${NAMESPACE}
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	namespace Plg\Pro_critical\Models;
	
	
	use Exception;
	use Joomla\CMS\MVC\Model\BaseDatabaseModel as JModelLegacy;
	use Joomla\CMS\Factory as JFactory;
	
	/**
	 * Работа с моделями каопонента
	 * Сохранение - Удаление
	 * @since       version
	 * @package     Plg\Pro_critical\Models
	 *
	 */
	class Route
	{
		private $app  ;
		private static $component = 'pro_critical';
		private static $prefix = 'pro_critical' . 'Model';
		
		/**
		 * Route constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		public function __construct ()
		{
			$this->app = JFactory::getApplication() ;
			JModelLegacy::addIncludePath( JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_' . self::$component . DS . 'models', self::$prefix );
		}
		
		public function save( $output = false ){
			if( !$output )
			{
				$form = $this->app->input->get('data' , false , 'RAW');
				parse_str( $form, $output );
			}#END IF
			
			
			
			$model =  ( explode('.' , $output['task'] ) )[0] ;
			$jform = $output['jform'] ;
			
			$Model = JModelLegacy::getInstance( $model , self::$prefix );
			
			if( !$Model->save($jform) )
			{
				$mes = 'Сохранение параметров не удалось!';
				$this->app->enqueueMessage( $mes , 'warning' );
				return true;
			}#END IF
			
			$mes = 'Параметры сохранены!';
			$this->app->enqueueMessage( $mes  );
			return true;
			
			
		}
	}