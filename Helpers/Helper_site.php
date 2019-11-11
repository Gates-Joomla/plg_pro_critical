<?php
	/**
	 * общие методы плагина
	 * @package     Plg\Pro_critical
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Plg\Pro_critical;
	
	
	use Joomla\CMS\Uri\Uri;
	use Exception;
	use JComponentHelper;
	
	class Helper_site
	{
		
		/**
		 * Get front-end default language
		 * @return string
		 * @since 3.9
		 */
		public static function getDefaultLanguage ()
		{
			$params = JComponentHelper::getParams( 'com_languages' );
			return $params->get( 'site' , 'en-GB' );
		}
		
		/**
		 * Узнать, что SSL-сертификат установлен на внешнем сервере
		 * @param $domain
		 *
		 * @return bool
		 *
		 * @see http://qaru.site/questions/1898622/how-to-find-out-ssl-certificate-is-installed-on-a-server-using-php
		 * @since version
		 */
		public static function is_exist_ssl($domain){
			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, "https://".$domain);
			curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt ($ch, CURLOPT_HEADER, 0);
			curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1);
			curl_exec($ch);
			
			if(!curl_error($ch)){
				$info = curl_getinfo($ch);
				if($info['http_code'] == 200){
					return true;
				}
				return false;
			}else{
				return false;
			}
		}
		
		/**
		 * Подобрать подходящий протокол для адреса
		 *
		 * @since version
		 */
		public static function findSchemeToUrl($url){
			
			$urlParse  = parse_url( $url );
			$testRes = self::is_exist_ssl($urlParse['host']);
			
			if( preg_match( '/^\/\//' , $url ) )
			{
				$url = preg_replace('/^\/\//', '', $url);
			}
			
			if( $testRes )
			{
				return 'https://'.$url ;
			}#END IF
			return 'http://'.$url ;
		}
		
		/**
		 * Исправление ошибок после анализа $url
		 * @param $logErrArr
		 * @param $url
		 * @return string
		 * @throws Exception
		 * @since version
		 */
		public static function correctionExternalAddress($logErrArr , $url ){
			
			foreach( $logErrArr as $itemArr )
			{
				switch($itemArr['code']){
					case '101':
						$url = self::findSchemeToUrl($url) ;
						
						break ;
					default:
						$mes = 'Plg\Pro_critical\Helper_site::correctionExternalAddress() - Неизвестный код ошибки' ;
						throw new Exception( $mes , 500 );
				}
			}#END FOREACH
			return $url ;
		}
		
		/**
		 * Загрузить внешний ресурс
		 * @param $request_url
		 * @return bool|string
		 * @throws Exception
		 * @since version
		 */
		public static function get_url( $request_url) {
			
			$checkUrl = self::linkAnalysis($request_url) ;
			if( count($checkUrl['err']) )
			{
				$request_url = self::correctionExternalAddress($checkUrl['err'] ,$request_url ) ;
			}#END IF
			
			
			
			$curl_handle = curl_init();
			curl_setopt($curl_handle, CURLOPT_URL, $request_url);
			curl_setopt($curl_handle, CURLOPT_CONNECTTIMEOUT, 0);
			curl_setopt($curl_handle, CURLOPT_TIMEOUT, 0);
			curl_setopt($curl_handle, CURLOPT_SSL_VERIFYPEER, FALSE);
			curl_setopt($curl_handle, CURLOPT_USERAGENT, $_SERVER['HTTP_USER_AGENT']);
			curl_setopt($curl_handle, CURLOPT_FOLLOWLOCATION, 1);
			curl_setopt($curl_handle, CURLOPT_RETURNTRANSFER, TRUE);
			$JsonResponse = curl_exec($curl_handle);
			$http_code = curl_getinfo($curl_handle);
			return($JsonResponse);
		}
		
		/**
		 * Проверка хоста ссылки локалный или внешний
		 * @param  string $href - Url ссылка
		 * @return bool - если локальный TRUE иначе FALSE
		 *
		 * @since 3.9
		 */
		protected static function checkLocalHost ( $href    )
		{
			$protocol = parse_url( $href );
			# для ссылок вида //joomla-upd.ga/test_css/test_home.css
			# если домен действительно содержит точку
			# и он не root домен сайта (не имеет вхождений в Uri::root() )
			if( stristr( $protocol[ 'host' ] , '.' ) && !stristr( Uri::root() , $protocol[ 'host' ] ) )
			{
				return false ;
			}
			return true ;
		}
		
		/**
		 * Разбор ссылки - поиск ошибок - исправление ссылки - определение локальная ссылка или нет
		 * @param $href
		 * @return array - []
		 * @since 3.9
		 */
		public static function linkAnalysis ( $href  )
		{
			
			$config = \Joomla\CMS\Factory::getConfig();
			$force_ssl = $config->get('force_ssl');
			$log = [
				'file' => null ,
				'no_external' => false ,
				'err' => [] ,
				'protocol' => [] ,
				
				'absolute_path' => false ,
				'err_href' => null ,
				'is_error' => false ,
			];
			
			$copyOrigHref = $href ;
			
			$href = trim( $href );
			
			if( preg_match( '/\s/' , $href ) )
			{
				$log[ 'err' ][] = [
					'text' => 'В ссылке присутствую пробелы это може привести к ошибкам' ,
					'code' => '100'
				];
			}#END IF
			
			if( preg_match( "/[а-яё]+/iu" , $href ) )
			{
				$log[ 'err' ][] = 'В ссылке присутствую русские буквы.';
			}
			
			$protocol             = parse_url( $href );
			$isLocalHost          = self::checkLocalHost( $href );
			
			# Проверка протокола
			if( !isset( $protocol[ 'scheme' ] ) )
			{
				if( !$isLocalHost )
				{
					$log[ 'err' ][] = [
						'text' => 'Отсутствует протокол (scheme) в адресе' ,
						'code' => '101'
					];
				}#END IF
			}
			else if( stristr( $protocol[ 'scheme' ] , 'http' ) )
			{
				# Если не ssl и внешний
				if( $protocol[ 'scheme' ] == 'http' )
				{
					if( !$isLocalHost )
					{
						# Если на сайте включено SSL
						if( $force_ssl == 2 )
						{
							$log[ 'err' ][] = [
								'text' => 'Протокол ссылки без SSL! но этот сайт с SSL' ,
								'code' => '102'
							];
						}#END IF
						
					}
					else
					{
						# Если на сайте включено SSL
						if( $force_ssl == 2 )
						{
							$log[ 'err' ][] = [
								'text' => 'Протокол ссылки без SSL! Протокол сайта с SSL. Это приведет к ошибкам при загрузки данного ресурса.' ,
								'code' => '103'
							];
						}#END IF
					}#END IF
				}
			}
			else
			{
				$log[ 'err' ][] = [
					'text' => 'Тип протокола не определен' ,
					'code' => '104'
				];
			}#END IF
			
			# Проверить домен
			if( $isLocalHost  && isset( $protocol[ 'host' ] )   )
			{
				$protocolSite             = parse_url( Uri::root() );
				if( $protocolSite['host'] == $protocol['host'] )
				{
					$log[ 'err' ][] = [
						'text' => 'Для локальной ссылки указан абсолютный путь' ,
						'code' => '105'
					];
					$log['absolute_path'] = true ;
				}#END IF
			}#END IF
			# Проверка path
			if( stristr( $protocol[ 'path' ] , '//' ) )
			{
				$log[ 'err' ][] = [
					'text' => 'Путь содержит два слеша после домена' ,
					'code' => '106'
				];
				$copyPath       = preg_replace( '/^\/\//' , '/' , $protocol[ 'path' ] );
				$href           = str_replace( '/' . $copyPath , $copyPath , $href );
				
			}
			else
			{
				# если host - не содержит точку
				if( !stristr( $protocol[ 'host' ] , '.' ) && $isLocalHost && preg_match( '/^\/\//' , $href ) )
				{
					$log[ 'err' ][] = [
						'text' => 'Ошибка в адресе локального файла. Два слеша в начале относительного пути' ,
						'code' => '107'
					];
					$href           = '/' . $protocol[ 'host' ] . $protocol[ 'path' ];
				}#END IF
			}
			$log [ 'file' ]     = $href;
			$log [ 'no_external' ] = $isLocalHost;
			$log [ 'protocol' ] = $protocol;
			
			if( count( $log[ 'err' ] ) )
			{
				$log [ 'err_href' ] = $copyOrigHref;
				$log [ 'is_error' ] = true;
				$logErrTextArr = self::implode_log_err( $log['err'] ) ;
				$log [ 'err_path_log' ] = implode("\n" , $logErrTextArr );
			}#END IF
			return $log ;
		}
		
		/**
		 * Массив ошибок в строку - [CODE]-MESS
		 * @param $arrArr
		 * @return array
		 * @since version
		 */
		private static function implode_log_err ($arrArr ){
			$arr = [] ;
			foreach( $arrArr as $item )
			{
				$arr[] = '['.$item['code'].']-'.$item['text'] ;
			}#END FOREACH
			return $arr ;
		}
		
		
		
		
	}
	
	
	