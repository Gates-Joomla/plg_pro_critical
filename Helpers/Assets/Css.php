<?php
	/**
	 * @package     Css_file
	 * @subpackage
	 *
	 * @copyright   A copyright
	 * @license     A "Slug" license name e.g. GPL2
	 */
	
	namespace Plg\Pro_critical\Helpers\Assets;
	
	
	use JFactory;
	use JLoader;
	use JModelLegacy;
	use Exception;
	use JDate;
	use Joomla\CMS\Uri\Uri;
	use Throwable;
	
	/**
	 * @since       version
	 * @package     Plg\Pro_critical\HelpersCss
	 *
	 */
	class Css
	{
		private $app;
		public static $instance;
		
		public $statistics = [];
		
		public $BASE_LINK;
		
		
		/**
		 * Имя компонента для вызова модели
		 * @since 3.9
		 * @var string
		 */
		private static $component = 'pro_critical';
		private static $prefix = 'pro_critical' . 'Model';
		
		
		private $Css_file_list;
		
		
		/**
		 * helper constructor.
		 * @throws Exception
		 * @since 3.9
		 */
		private function __construct ( $options = [] )
		{
			$this->app = JFactory::getApplication();
			JLoader::register( 'Pro_criticalHelper' , JPATH_ADMINISTRATOR . '/components/com_pro_critical/helpers/pro_critical.php' );
			JModelLegacy::addIncludePath( JPATH_ADMINISTRATOR . DS . 'components' . DS . 'com_' . self::$component . DS . 'models' , self::$prefix );
			$this->Css_file_list = JModelLegacy::getInstance( 'Css_file_list' , self::$prefix );
			
			# Установить поля в статистику
			$this->statistics = [ 'New_fiels' => [] , 'Load_fiels' => [] , 'minifyCount' => 0 ];
			
			return $this;
		}#END FN
		
		/**
		 * @param   array  $options
		 *
		 * @return Css
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
		 * Найти и извлечь все ссылки на CSS файлы и теги стили
		 * Добавить новые данные в справочники.
		 * Совместить найденые данные с настройками компонента
		 *
		 * @throws Exception
		 * @since 3.9
		 */
		public function getFileList ()
		{
			$app = JFactory::getApplication();
			$Links_assets = \Plg\Pro_critical\Helpers\Assets\Links_assets ::instance() ;
			
			
			# Загрузить все данные из справочника CSS FILE
			$app->input->set( 'limit' , 0 ); // Снять лимит на количество записей DEF - 20
			$Css_file_list = $this->Css_file_list->getItems();
			
			
			$body = $this->app->getBody();
			
			# Найти все Style элементы в теле страницы
			$dom = new \GNZ11\Document\Dom();
			$dom->loadHTML( $body );
			$xpath = new \DOMXPath( $dom );
			$Nodes = $xpath->query( '//link[@rel="stylesheet"]|//style' );
			$link  = [];
			$styleTag = [] ;
			foreach( $Nodes as $node )
			{
				switch( $node->tagName )
				{
					case 'link':
						$attr    = $dom::getAttrElement( $node , [ 'rel' ] );
						$hrefArr = explode( '?' , $attr[ 'href' ] );
						
						unset( $attr[ 'href' ] );
						
						$href = $hrefArr[ 0 ] ;
						
						
						# Разбор ссылки - поиск ошибок - исправление ссылки - определение локальная ссылка или нет
						$log   = $Links_assets->linkAnalysis( $href  );
						$href = $log['file'];
						$link[ $href ] = [] ;
						$link[ $href ]['load'] = 1 ;
						
						$link[ $href ] = array_merge( $link[ $href ] , $log  ) ;
						
						$link[ $href ]           = array_merge( $link[ $href ] , $attr );
						
						# Если есть параметры в ссылке
						if( isset( $hrefArr[ 1 ] ) )
						{
							# Разобрать параметры ссылки
							$link[ $href ][ 'params_query' ] = $this->parseRequestParameters( $hrefArr , $link , $href );
						}#END IF
						
						$this->statistics['errors'] += ( count( $log['err'] )) ;
						
						// $link[ $href ]['errors'] = $log['err'];
						
						
						break;
					case 'style' :
						$styleTag[] = [
							'load'=> 1 ,
							'content'=>$node->nodeValue ,
							'hash' => md5( $node->nodeValue ) ,
						] ;
						break;
				}#END SWICH
				
				
				# Удалить найденый узел
				$node->parentNode->removeChild( $node );
				
				
				
				
			}#END FOREACH
			
			
			
			
			$body = $dom->saveHTML();
			$this->app->setBody( $body );
			
			$UbdateCssFile = [];
			
			# Скопировать набор найденых файлов
			$this->BASE_LINK = $link;
			
			# Совмещение извлеченных данны с настройкам загрузок
			foreach( $Css_file_list as $item )
			{
				# Если найденный файл есть в справочнике
				if( isset( $link[ $item->file ] ) )
				{
					# Дополняем ссылку данными со справочника
					$this->BASE_LINK[ $item->file ] = $item;
					# Исключаем ссылку из добавления в справочник
					unset( $link[ $item->file ] );
					
					$this->statistics[ 'Load_fiels' ][] = $item->file;
					if( $item->minify ) $this->statistics[ 'minifyCount' ]++;
				}
			}#END FOREACH
			
			# STAT - новые найденые файлы 
			$this->statistics[ 'New_fiels' ] = array_keys( $link );
			# STAT - файлы которые будут загружены
			$this->statistics[ 'Load_fiels' ] = array_keys( $this->BASE_LINK );
			
			# Добавить в справочник новые найденные файлы
			$this->addNewLink( $link );

			$this->addNewTagStyle($styleTag);
		}
		
		private function addNewTagStyle($styleTag){
			
			$excludeFields=['err','protocol','absolute_path',  ];
			
			if( !count( $styleTag ) ) return true;
			
			$db = JFactory::getDbo();
			$query   = $db->getQuery( true );
			$jdata   = new JDate();
			$now     = $jdata->toSql();
			$userId  = JFactory::getUser()->id;
			
			$columns = [] ;
			$firstElement = reset($styleTag );
			
			foreach( $firstElement as $key => $itemFile )
			{
				if(  in_array( $key , $excludeFields ) ) {
					continue ;
				}#END IF
				$columns[]= $key ;
			}#END FOREACH
			
			$realColumns = $columns ;
			
			$columns[] = 'created_by';
			$columns[] = 'created';
			
			foreach( $styleTag as  $itemFile )
			{
				$valuesArr =[] ;
				foreach( $realColumns as $key   )
				{
					$item = $itemFile[$key] ;
					$valuesArr[] =  $db->quote( $item ) ;
					
				}#END FOREACH
				$valuesArr[] = $db->quote( $userId ) ;
				$valuesArr[] = $db->quote( $now ) ;
				$query->values( implode( "," , $valuesArr) );
			}//foreach
			
			$query->insert( $db->quoteName( '#__pro_critical_css_style' ) )->columns( $db->quoteName( $columns ) );
			$db->setQuery( $query );
			
			try
			{
				// Code that may throw an Exception or Error.
				$db->execute();
			}
			catch( Exception $e )
			{
				// Executed only in PHP 5, will not be reached in PHP 7
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo'<pre>';print_r(  $e );echo'</pre>'.__FILE__.' '.__LINE__;
				die(__FILE__ .' '. __LINE__ );
			}
			catch( Throwable $e )
			{
				// Executed only in PHP 7, will not match in PHP 5
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo '<pre>'; print_r( $e ); echo '</pre>' . __FILE__ . ' ' . __LINE__;
				die( __FILE__ . ' ' . __LINE__ );
			}
			
			return true;
			
		/*	echo'<pre>';print_r( $query->dump() );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $realColumns );echo'</pre>'.__FILE__.' '.__LINE__;
			echo'<pre>';print_r( $styleTag );echo'</pre>'.__FILE__.' '.__LINE__;
			die(__FILE__ .' '. __LINE__ );*/
		}
		
		
		/**
		 * Установить в HTML ссылки на Css файлы и стили
		 *
		 * @throws Exception
		 * @since version
		 */
		public function insertStylesIntoDocument(){
			
			$dom = new \GNZ11\Document\Dom();
			
			# Подключить Helper для работы со сылками
			$Links_assets = \Plg\Pro_critical\Helpers\Assets\Links_assets ::instance() ;
			
			# массив для HTML элементов
			$tagsArr = [] ;
			foreach( $this->BASE_LINK as $url => $Link )
			{
				if( isset($Link->load)  &&  !$Link->load  ) continue ; #END IF
				
				# Пропустить если отложенная загрузка
				if ( isset($Link->delayed_loading)  && $Link->delayed_loading) continue ; #END IF
				
				$Linkcopy = $Link ;
				unset($Linkcopy->id);
				$Linkcopy->rel="stylesheet";
				
				# Подготовить ссылку к загрузи - определить параметры ссылки
				$LinkData = $Links_assets->prepareLinkData( $Linkcopy );
				
				# установить ссылку вниз Tag Head
				$dom::writeBottomHeadTag('link' , null , $LinkData );
				
//				echo'<pre>';print_r( $LinkData );echo'</pre>'.__FILE__.' '.__LINE__;
//				die(__FILE__ .' '. __LINE__ );
				
			
				
				
				
			}#END FOREACH
//			die(__FILE__ .' '. __LINE__ );
		}
		
		/**
		 * Добавить в справочник новые найденные файлы
		 *
		 * @param $link
		 *
		 * @return bool
		 *
		 * @since version
		 */
		private function addNewLink ( $link , $excludeFields =[] )
		{
			
			$excludeFields=['err','protocol','absolute_path',  ];
			
			if( !count( $link ) ) return true;
			
			$db = JFactory::getDbo();
			$query   = $db->getQuery( true );
			$jdata   = new JDate();
			$now     = $jdata->toSql();
			$userId  = JFactory::getUser()->id;
			
			$columns = [] ;
			$firstElement = reset($link );
			
			foreach( $firstElement as $key => $itemFile )
			{
				if(  in_array( $key , $excludeFields ) ) {
					continue ; 
				}#END IF
				$columns[]= $key ; 
			}#END FOREACH
			
			$realColumns = $columns ;
			
			$columns[] = 'created_by';
			$columns[] = 'created';
			
			
			
			
			
			
			foreach( $link as  $itemFile )
			{
				$valuesArr =[] ; 
				foreach( $realColumns as $key   )
				{
					$item = $itemFile[$key] ;
					$valuesArr[] =  $db->quote( $item ) ;
					
				}#END FOREACH
				$valuesArr[] = $db->quote( $userId ) ;
				$valuesArr[] = $db->quote( $now ) ;
				$query->values( implode( "," , $valuesArr) );
			}//foreach
			
			
			$query->insert( $db->quoteName( '#__pro_critical_css_file' ) )->columns( $db->quoteName( $columns ) );
			$db->setQuery( $query );
	 
			try
			{
				// Code that may throw an Exception or Error.
				$db->execute();
			}
			catch( Exception $e )
			{
				// Executed only in PHP 5, will not be reached in PHP 7
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo'<pre>';print_r(  $e );echo'</pre>'.__FILE__.' '.__LINE__;
				die(__FILE__ .' '. __LINE__ );
			}
			catch( Throwable $e )
			{
				// Executed only in PHP 7, will not match in PHP 5
				echo 'Выброшено исключение: ' , $e->getMessage() , "\n";
				echo '<pre>'; print_r( $e ); echo '</pre>' . __FILE__ . ' ' . __LINE__;
				die( __FILE__ . ' ' . __LINE__ );
			}
			
			return true;
		}
		
		/**
		 * Разобрать параметры запроса
		 *
		 * @param   array  $hrefArr
		 * @param   array  $link
		 * @param          $href
		 *
		 * @return array
		 *
		 * @since version
		 */
		protected function parseRequestParameters ( array $hrefArr , array $link , $href )
		{
			$paramHrefArr = explode( '&' , $hrefArr[ 1 ] );
			$i            = 0;
			foreach( $paramHrefArr as $item )
			{
				$paramArr                                                          = explode( '=' , $item );
				$nam                                                               = $paramArr[ 0 ];
				$val                                                               = $paramArr[ 1 ];
				$link[ $href ][ 'params_query' ][ 'params_query' . $i ][ 'name' ]  = $nam;
				$link[ $href ][ 'params_query' ][ 'params_query' . $i ][ 'value' ] = $val;
				$i++;
			}#END FOREACH
			return json_encode( $link[ $href ][ 'params_query' ] );
		}
		
		
		
		
		
		
		
	}
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	
	