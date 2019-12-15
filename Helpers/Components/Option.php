<?php
	
	
	namespace Plg\Pro_critical\Components;
	
	
	use JFactory;
	use Exception;
	use Joomla\CMS\Component\ComponentHelper;
	use Pro_criticalHelper;
	
	class Option
	{
		public static $option_id;
		private $app;
		public static $instance;
		
		/**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = JFactory::getApplication();
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return Option
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
		 * Получить id текущего компонента
		 * Getter Options Id
		 *
		 * @return mixed
		 * @throws Exception
		 * @since 3.9
		 */
		public function getOptionId ()
		{
			if( self::$option_id ) return self::$option_id; #END IF
			
			$option = $this->app->input->get('option' , false ) ;
			# Получить информацию о компоненте
			$Extension =   ComponentHelper::getComponent($option, $strict = false);
			
			self::$option_id = Pro_criticalHelper::getVar( 'directory_components' , $Extension->id , 'extension_id' );
			
			if( self::$option_id ) return self::$option_id; #END IF
			
			self::$option_id  = $this->addNewComponent();
			
			return   self::$option_id ;
		}#END FN
		
		/**
		 * Добавить новый компонент в справочник
		 * @throws Exception
		 * @since 3.9
		 */
		public function addNewComponent(){
			$option = $this->app->input->get('option' , false ) ;
			
			
			# Получить информацию о компоненте
			$ExtensionInfo = Pro_criticalHelper::getExtension( $option ) ;
			
			# Создаем ноывй компонент в справочнике
			$data = [] ;
			$data['copmonent_name'] = $ExtensionInfo['name'] ;
			$data['value_option'] = $option ;
			$data['extension_id'] = $ExtensionInfo['extension_id']  ;
			
			
			$model = Pro_criticalHelper::getModel( 'directory_components', JPATH_ADMINISTRATOR . '/components/com_pro_critical/') ;
			if( !$model->save($data)  )
			{
				throw new \Exception('Error! при создании значения в справочнике компонентов.');
			}#END IF
			return Pro_criticalHelper::getVar( 'directory_components' , '' . $ExtensionInfo['extension_id'] , 'extension_id' );
		}
		
		
	}