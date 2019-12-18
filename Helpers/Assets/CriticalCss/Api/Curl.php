<?php
	
	
	namespace Plg\Pro_critical\Helpers\Assets\CriticalCss\Api;
	
	
	use Plg\Pro_critical\Helpers\Assets\CriticalCss\Api;
	
	class Curl
	{
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
		public static function sendPost ( $ArrData , $ajax = false )
		{
			$myCurl = curl_init();
			curl_setopt_array( $myCurl, [
				CURLOPT_URL            => Api::$urlServer ,
				CURLOPT_TIMEOUT        => 400,
				CURLOPT_RETURNTRANSFER => true,
				CURLOPT_POST           => true,
				CURLOPT_POSTFIELDS     => http_build_query($ArrData),
			] );
			
			$response = curl_exec( $myCurl );
			
			curl_close( $myCurl );
			$data = json_decode( $response  )  ;
			if ( empty($data) ) throw new \Exception(Api::$urlServer  . ' Сервис недоступен'); #END IF
			return $data ;
		}
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	