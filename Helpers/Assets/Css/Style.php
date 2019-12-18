<?php
	namespace Plg\Pro_critical\Helpers\Assets\Css;
	
	use Exception;
	use Plg\Pro_critical\Helpers\Assets\Css;
	use Plg\Pro_critical\Helpers\Assets\Assets;
	
	class Style
	{
		/**
		 * @var \Joomla\CMS\Application\CMSApplication
		 * @since 3.9
		 */
		private $app;
		
		/**
		 * Style constructor.
		 * @throws Exception
		 * @since 3.9 
		 */
		public function __construct ()
		{
			$this->app =  \JFactory::getApplication() ;
		}
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
				
				css::$UseCritical ;
				css::$StackStyle ;
				
				# Если критичиские стили не созданы
				# Если способ загрузки Normal каждый стиль в своем теге
				if( !css::$UseCritical && $css_style_load_method )
				{
					# установить ссылку вниз Tag <head>
					$dom::writeBottomHeadTag( 'style' , $Css_styleData  );
					$Css_styleData = null;
					
				}#END IF
			}#END FOREACH
			
			# Если критичиские стили не созданы
			# Если способ загрузки Join - все стили в одном теге
			if( !css::$UseCritical && !$css_style_load_method )
			{
				# установить ссылку вниз Tag <head>
				$dom::writeBottomHeadTag( 'style' , $Css_styleData  );
			}#END IF
			
			
			# Если критичиские стили созданы 
			if( css::$UseCritical )
			{
				css::$StackStyle['style'] = self::$StyleHostory ;
				
				
			}#END IF
			
			if( self::$StopForCritical    ) self::$StyleHostory = [] ; #END IF
		}
		
		public function getAjaxStyleData(){
			$style = $this->app->input->get('style' , false , 'RAW' ) ;
			$styleArr = json_decode($style) ;
			
			# Получть объекты стилей из списка ID
			$res = Assets::getAssetsById( $styleArr  , 'css_style' , 'id' );
			#Сортируем массив в порядке загрузки на странице
			$css_styleArr = \Plg\Pro_critical\Helpers\Assets\CriticalCss\Api::sortArr( $styleArr , $res );
			$returnStyle = null ;
			foreach( $css_styleArr as $item )
			{
				# Подготовить стиле к загрузи - определить параметры стилей
				$returnStyle .= \Plg\Pro_critical\Helpers\Assets\Css\Style::prepareStyleData( $item );
				
			}#END FOREACH
			
			echo new \JResponseJson(['style' => $returnStyle]);
			$this->app->close();
			
			echo'<pre>';print_r( $returnStyle );echo'</pre>'.__FILE__.' '.__LINE__;
//			echo'<pre>';print_r( $css_styleArr );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );
		}
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	