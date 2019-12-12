<?php
	namespace Plg\Pro_critical\Helpers\Assets\Css;
	
	use Exception;
	
	class Style
	{
		
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
				
				# Подготовить стиле к загрузи - определить параметры стилей
				$Css_styleData .= \Plg\Pro_critical\Helpers\Assets\Css\Style::prepareStyleData( $item );
				
				# Если способ загрузки Normal
				if( $css_style_load_method )
				{
					# установить ссылку вниз Tag Head
					$dom::writeBottomHeadTag( 'style' , $Css_styleData  );
					$Css_styleData = null;
					
				}#END IF
			}#END FOREACH
			
			# Если способ загрузки Join
			if( !$css_style_load_method )
			{
				# установить ссылку вниз Tag Head
				$dom::writeBottomHeadTag( 'style' , $Css_styleData  );
			}#END IF
		}
		
		
		
	}