<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\CriticalCss;
	
	use Plg\Pro_critical\Helpers\Assets\Assets;
	use Plg\Pro_critical\Helpers\Assets\Links;
	class Api
	{
		/**
		 * @var \Joomla\CMS\Application\CMSApplication
		 */
		private $app;
		
		/**
		 * Api constructor.
		 */
		public function __construct ()
		{
			$this->app =  \JFactory::getApplication() ;
		}
		
		/**
		 * Точка входа Ajax
		 * @throws \Exception
		 * @since 3.9
		 */
		public function onAjaxApiCritical ()
		{
			# TODO Refactoring - для других задач
			$dataJson = $this->app->input->get('data' , null , 'RAW') ;
			$data = json_decode( $dataJson ) ;
			
			$res = Assets::getAssetsById( $data->cssLinkHistory  , 'css_file' , 'id' );
			#Сортируем массив в порядке загрузки на странице
			$css_fileArr = self::sortArr($data->cssLinkHistory , $res );
			
			
			$res = Assets::getAssetsById( $data->StyleHostory  , 'css_style' , 'id' );
			#Сортируем массив в порядке загрузки на странице
			$css_styleArr = self::sortArr( $data->StyleHostory , $res );
			
			# Получить ссылки на файлы
			$arrFiles = $this->getLinksFiles($css_fileArr) ;
			
			
			$Files = new Files() ;
			# читаем все файлы
			try
			{
				$Files->addContentFile( $arrFiles );
			}
			catch( \Exception $e )
			{
				echo'<pre>';print_r( $e );echo'</pre>'.__FILE__.' '.__LINE__;
				die(__FILE__ .' '. __LINE__ );
			}
			
			$ALLCSS = $this->getAllCss ( $fArr , $sArr ) ;
			
			
			
			echo'<pre>';print_r( $css_styleArr );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );
		}
		
		/**
		 * Собрать весь css вместе
		 * @param $fArr array cssLinkHistory
		 * @param $sArr array StyleHostory
		 *
		 * @return string|null
		 * @since 3.9
		 */
		private function getAllCss ( $fArr , $sArr ){
			$res = null ;
			foreach( $fArr as $item )
			{
				$res .= $item->content ; 
			}#END FOREACH
			foreach( $sArr as $item )
			{
				$res .= $item->content ;
			}#END FOREACH
			return $res ;
		}
		
		/**
		 * Подготовить ссылки на файлы
		 * @param $arrFiles
		 *
		 * @return array
		 * @since 3.9
		 */
		private function getLinksFiles( array $arrFiles){
			
			foreach( $arrFiles as $i => $File )
			{
				# Подготовить ссылку к загрузи - определить параметры ссылки
				$arrFiles[$i]  = Links::prepareLinkData( $File , 'css' , true );
				
			}#END FOREACH
			
			return $arrFiles ;
		}
		
		/**
		 * Сортируем массив в порядке загрузки на странице
		 * @param $arrKeys
		 * @param $arrData
		 *
		 * @return array
		 * @since 3.9
		 */
		public static function sortArr($arrKeys , $arrData ){
			$retArr = [] ;
			foreach( $arrKeys as $Key )
			{
				$retArr[] = $arrData[$Key] ;
			}#END FOREACH
			return $retArr ;
		}
		
		
		
		
		
		
		
		
		
		
		
		
		
		private $urlServer = 'https://nobd.ga/' ;
		
		
		
		
		
		
		/**
		 * Отправка запроса для выделения критических стилей
		 *
		 * @param $Arrdata
		 *
		 * @return bool|mixed FALSE - Если сервер недуступен и копия стелей не найдена в локальном хранилище
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 10.01.19
		 * @throws \Exception
		 */
		private function generateCriticalCss ( $Arrdata )
		{
			$Arrdata = [
				'task' => 'getCtiticalCss' ,
				'cssUrl'  => \JURI::root() . $fileUrl,
				'urlSite' =>  $opt['url'] ,
				'userAgent' => $UserAgentData['ua'] ,
			];
			$Arrdata['width'] = $this->Device_id_size->width ;
			$Arrdata['height']= $this->Device_id_size->height ;
			
			
		}
			
			
			/**
		 * Отправить запрос nobd.ml
		 *
		 * @param $ArrData
		 * @param $ajax
		 *
		 * @return mixed
		 * @throws \Exception
		 * @throws \Throwable
		 * @author    Gartes
		 *
		 * @since     3.8
		 * @copyright 29.12.18
		 */
		public function sendPost ( $ArrData , $ajax = false )
		{
			
			$myCurl = curl_init();
			curl_setopt_array( $myCurl, [
				CURLOPT_URL            => $this->urlServer ,
				CURLOPT_TIMEOUT        => 400,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => http_build_query($ArrData),
			] );
			
			$response = curl_exec( $myCurl );
			
			curl_close( $myCurl );
			$data = json_decode( $response  )  ;
			
			
			if ( empty($data) ) throw new \Exception($this->urlServer . ' Сервис недоступен'); #END IF
			
			
			$PlatformCurl = \Core\platform\PlatformCurl::instance();
			$PlatformCurl->responseMessage($data);
			
			if ($ajax){
				# обьединить локальные сообщения с собщениями ответа CURL
				$ajax->mergeMessage( $PlatformCurl->Messages );
			}
			
			return $data;
			
		}#END FN
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	