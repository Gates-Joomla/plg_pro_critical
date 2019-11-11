<?php
	/**
	 * @package     Plg\Pro_critical\Helpers\Assets
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	
	use Joomla\CMS\Component\ComponentHelper;
	use Joomla\CMS\Filesystem\Folder;
	use Plg\Pro_critical\Helper_site;
	use Exception;
	use JDate;
	
	
	class External
	{
		
		protected $paramsComponent ;
		private $external_cache_directory_default = '/media/com_pro_critical/cashe_access' ;
		/**
		 * External constructor.
		 * @since 3.9
		 */
		public function __construct ()
		{
			$this->app = \Joomla\CMS\Factory::getApplication() ;
			
			
			
			$this->paramsComponent = ComponentHelper::getParams( 'com_pro_critical' );
			
			# Todo вывод параметров компонента shorten_setting -----
//			$shorten_setting = $this->paramsComponent->get('shorten_setting' , false ) ;
//			echo'<pre>';print_r( json_encode( $shorten_setting ) );echo'</pre>'.__FILE__.' '.__LINE__;
//			die(__FILE__ .' '. __LINE__ );
			
		}
		
		/**
		 * Кешировать внешний файл
		 *
		 * @throws Exception
		 * @since version
		 */
		public function saveExternal(){
			# Класс для работы с моделями
			$Route = new \Plg\Pro_critical\Models\Route() ;
			
			$external_cache_directory = $this->paramsComponent->get('external_cache_directory' , $this->external_cache_directory_default ) ;
			$cache_directory = JPATH_ROOT . $external_cache_directory ;
			
			$form = $this->app->input->get('data' , false , 'RAW');
			parse_str( $form, $dataForm );
			
			$originalFilePath = $dataForm['jform']['file'] ;
			
			switch($dataForm['task']){
				case 'css_file.edit':
					$type = 'css' ;
					
					break ;
					
				default :
					$this->app->enqueueMessage('Неизвестный тип файла!' , 'error');
					return true;
			}
			
			$fileName = basename($originalFilePath);
			
			$cache_directory = JPATH_ROOT . $external_cache_directory.'/'.$type ;
			
			$filePath = $cache_directory.'/'.$fileName ;
			$filePathUrlOverride = $external_cache_directory .'/'.$type.'/'.$fileName ;
			
			# Если команда не кешировать внешний ресурс
			if( !$dataForm['jform']['cash_external'] )
			{
				if( \Joomla\CMS\Filesystem\File::delete($filePath) )
				{
					$mes = 'Локальный файл удален.';
					$this->app->enqueueMessage($mes);
					$dataForm[ 'jform' ][ 'override' ]      = 0;
					$dataForm[ 'jform' ][ 'override_file' ] = null;
					$dataForm[ 'jform' ][ 'last_update' ]   = null;
					
					if( $Route->save($dataForm) )
					{
						return $dataForm['jform']  ;
					}#END IF
					
					$mes = 'Не удалось сохранить настройки.';
					$this->app->enqueueMessage($mes , 'warning' );
					return $dataForm['jform']  ;
				}#END IF
				$mes = 'Не удалось удалить локальный файл.';
				throw new Exception( $mes , 500 );
				
				
			}#END IF
			
			
			if( !Folder::exists( $cache_directory ) )
			{
				Folder::create( $cache_directory );
			}#END IF
			
			
			
			
			$contents = Helper_site::get_url(   $originalFilePath );
			
			$handler = @fopen( $filePath , 'w' );
			
		
			if( !$handler )
			{
				fclose( $handler );
				$mes = 'Не возможно открыть файл для записи.' . '<br>';
				$mes .= 'Проверьте права доступа к файлу и директории.';
				throw new Exception( $mes , 500 );
				
			}#END IF
			fwrite( $handler , $contents );
			fclose( $handler );
			
			$jdata= new \Joomla\CMS\Date\Date();
			$now = $jdata->toSql();
			
			$dataForm['jform']['override'] = 1 ;
			$dataForm['jform']['override_file'] = $filePathUrlOverride ;
			$dataForm['jform']['last_update'] = $now ;
			
			
			
			if( $Route->save($dataForm) )
			{
				$mes = 'Внешнний файл загружен.<br>Создано локальное переопределение';
				$this->app->enqueueMessage($mes );
				return $dataForm['jform']  ;
			}#END IF
			
			
			
			return true ;
		}
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	