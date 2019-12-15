<?php
	namespace Plg\Pro_critical\Helpers\Assets\Css;
	
	use Exception;
	
	class Style
	{
		/**
		 * Массив ID скриптов которы установлены на страницы
		 * Исключаются файлы с отложеной загрузкой
		 * Будет пустым если хотябы один файл установлен без ID (т.е только что обнаружен)
		 * @since 3.9
		 * @var array
		 *
		 */
		static $StyleHostory = [] ;
		/**
		 * #индикатор - Не запускать создание критических стилей
		 * @var bool
		 * @since 3.9
		 */
		static $StopForCritical = false ;
		/**
		 * Подготовить данные стеля для вставки
		 * @param $dataStyle object - Style
		 *
		 * @return string - Style tag content
		 * @since 3.9
		 */
		public static function prepareStyleData( $dataStyle ){
			if( $dataStyle->content_min )
			{
				return $dataStyle->content_min ;
			}#END IF
			
			return $dataStyle->content ;
		}
		
		/**
		 * Установка тегов стилей в документ
		 *
		 * @param  $Link
		 *
		 *
		 * @throws Exception
		 * @since 3.9
		 */
		public static function setStyleTag ( $Link  )
		{
			$dom = new \GNZ11\Document\Dom();
			
			# получить настройки копанента
			$comparams             = \JComponentHelper::getParams( 'com_pro_critical' );
			$paramsComponent       = $comparams->toArray();
			$css_style_load_method = $paramsComponent[ 'css_style_load_method' ];
			
			
			$Css_styleData = null;
			foreach( $Link as $item )
			{
				if( isset( $item->load ) && !$item->load ) continue; #END IF
				# Пропустить если отложенная загрузка
				if( isset( $item->delayed_loading ) && $item->delayed_loading ) continue; #END IF
				
				if( isset( $item->id ) )
				{
					self::$StyleHostory[] = $item->id ;
				}else{
					# Установить индикатор - Не запускать создание критических стилей
					self::$StopForCritical = true ;
				}#END IF
				
				
				# Подготовить стиле к загрузи - определить параметры стилей
				$Css_styleData .= \Plg\Pro_critical\Helpers\Assets\Css\Style::prepareStyleData( $item );
				
				# Если способ загрузки Normal каждый стиль в своем теге
				if( $css_style_load_method )
				{
					# установить ссылку вниз Tag <head>
					$dom::writeBottomHeadTag( 'style' , $Css_styleData  );
					$Css_styleData = null;
					
				}#END IF
			}#END FOREACH
			
			# Если способ загрузки Join - все стили в одном теге
			if( !$css_style_load_method )
			{
				# установить ссылку вниз Tag <head>
				$dom::writeBottomHeadTag( 'style' , $Css_styleData  );
			}#END IF
			
			if( self::$StopForCritical ) self::$StyleHostory = [] ; #END IF
		}
		
		
		
	}